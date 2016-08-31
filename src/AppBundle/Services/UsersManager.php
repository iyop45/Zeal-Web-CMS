<?php
// src/AppBundle/Services/UsersManager.php

namespace AppBundle\Services;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Doctrine\ORM\EntityManager;

use AppBundle\Services\Interfaces;

class UsersManager
{
    private $repository;
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
        $this->repository = 'AppBundle\Entity\Users';
        $this->errorMessages = array();
    }
    
    //-----------------------------------------------------
    // SELECT actions
    //-----------------------------------------------------    
    
    public function get(array $options, array $filters)
    {
        // Set default values
        if(!isset($filters['sortBy'])){
            $filters['sortBy'] = 'Id';
        }

        if(!isset($filters['order'])){
            $filters['order'] = 'ASC';
        }
        
        if(!isset($filters['limit'])){
            $filters['limit'] = 1;
        }
    
        if(!isset($filters['offset'])){
            $filters['offset'] = 0;
        }
        
        if($filters['limit'] == 1){
            // Expecting a single result
            $user = $this->em
                         ->getRepository($this->repository)
                         ->findOneBy($options);
        }else{  
            $user = $this->em
                         ->getRepository($this->repository)
                         ->findBy($options, array($filters['sortBy'] => $filters['order']), $filters['limit'], $filters['offset']);
        }
        
        return $user; 
    }
    
    //-----------------------------------------------------
    // DELETE actions
    //-----------------------------------------------------        
    
    public function delete($User)
    {
        $this->em->remove($User);
        $this->em->flush();
    }
    
    //-----------------------------------------------------
    // UPDATE actions
    //----------------------------------------------------- 
    
    public function update($User, array $Options)
    {
        $User->setUsername($Username);
        $em->flush();      
    }
    
    //-----------------------------------------------------
    // INSERT actions
    //-----------------------------------------------------       
    public function add(array $Options)
    {
        $user = new Users();
        
        $user->setUsername($username);
        $user->setEmail($email);
        $user->setPassword(password_hash($password));
        
        $this->em = $this->getDoctrine()->getManager();
    
        // Tells Doctrine you want to (eventually) save the User (no queries yet)
        $this->em->persist($user);
    
        // Actually executes the queries (i.e. the INSERT query)
        $this->em->flush();        
        
    }    
    
    //-----------------------------------------------------
    // VALIDATION
    //-----------------------------------------------------   
 
    private $errorMessages;
    public function getErrorMessages(){
        return $this->errorMessages;
    }
    public function isValidEmail(string $Email, int $SiteId){
        if(!filter_var($Email, FILTER_VALIDATE_EMAIL)){ 
            array_push($this->errorMessages, 'Invalid email address');
            return false;
        }
        
        return true;
    }
    
    public function isValidUsername(string $Username, int $SiteId){}
    public function isValidPassword(string $Password){}
    public function isUniqueUsername(string $Username, $SiteId){}
    public function isUniqueEmail(string $Email, $SiteId){}
    
    private $error;
    public function getError(){
        return $this->error;
    }
    
    public function verifyCredentials(string $UsernameOrEmail, string $Password, $Site)
    {
        
        $query = $this->em->createQueryBuilder()
                ->select('u')
                ->from('AppBundle\Entity\Users', 'u')
                ->innerJoin('AppBundle\Entity\Sites', 's', 'WITH', 's.Id = u.Site')
                ->where('s.Id = :SiteId AND (u.Username = :Username OR u.Email = :Email)')
                ->setParameters(['SiteId' => $Site->getId(),
                                 'Username' => $UsernameOrEmail,
                                 'Email' => $UsernameOrEmail])
                ->getQuery();
        
        $User = $query->getOneOrNullResult();
        if($User){
            if(password_verify($Password, $User->getPassword())){
                return $User;
            }else{
                $this->error = "Invalid password";
                return false;
            }
        }else{
            $this->error = "Email or Username does not correspond to a user";
            return false;
        }
    }

    
}
