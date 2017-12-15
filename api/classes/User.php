<?php
class User{
   
    public static function login($user,$pass){
      global $db;
         if($db->query('SELECT user_name FROM users WHERE user_name = :username',array(':username' => $user))){
           if(password_verify($pass,$db->query('SELECT password FROM users WHERE user_name = :username',array(':username' => $user))[0]['password'])){

                 $cstring = True;
                 $token = bin2hex((openssl_random_pseudo_bytes(64,$cstring)));
                 
                 $user_id = $db->query("SELECT id FROM users WHERE user_name = :username",array(':username' => $user))[0]['id'];
               
                 $db->query("INSERT INTO login_tokens (token,user_id) VALUES (:token,:user_id)",array(':token'=> sha1($token),':user_id' => $user_id));  
                 echo '{"token":"'.$token.'"}';
             }else{
                 echo "Invalid username or password !!";
                 http_response_code(401);
             }

          }else{
              echo  "Invalid username or password !!";
              http_response_code(401);
          }  
    }  

    public static function signup($name,$password,$email){
      global $db;
               if(!$db->query("SELECT user_name FROM users WHERE user_name = :username",array(':username' => $name))){
          if(strlen($name) >= 3 && strlen($name) <= 60){
                  if(filter_var($email,FILTER_VALIDATE_EMAIL)){
                       if(strlen($password) >= 4 && strlen($password) <= 40){
                            if(preg_match('/[a-zA-Z0-9_]+/',$name)){
                                if(!$db->query("SELECT id FROM users WHERE email= :email",array(':email' => $email))){
                                     $db->query("INSERT INTO users (user_name,password,email) values(:username,:password,:email)",array(':username' => $name,':password' => password_hash($password,PASSWORD_DEFAULT),':email' => $email));
                                    return "Success !!";
                                 }else{
                                  return "Email already exists";
                                  http_response_code(409);
                                 }
                       
                              }else{
                                return "Invalid User Name!!";
                                http_response_code(409);
                              }
                              
                       }else{
                          return "Invalid password";
                          http_response_code(409);
                       }
                  }else{
                   return"Invalid Email";
                    http_response_code(409);
                  }
           }else{
              return "Invalid User Name !!";
              http_response_code(409);
           }
            
         } else{
            return "User Already Exists";
            http_response_code(409);
         }
   }


   public static function logout_once($token){
    global $db;
    $db->query('DELETE FROM login_tokens WHERE token = :token',array(':token' => sha1($token)));
          return '{"status" : "okay"}';
           http_response_code(200);
          
      }

      public static function logout_all($userid){
         global $db;
         $db->query('DELETE FROM login_tokens WHERE user_id = :userid',array(':userid' => $userid));
         return '{"status": "okay"}';
      }


      public static function search($search_key){
        global $db;  
        $search_keys = explode(" ",$search_key);
        if(count($search_keys) == 1){
           $search_keys = str_split($search_keys[0],2);
        }
        
        $whereclause = "";
        $params = array();
        $params[':search_key'] = '%'.$search_key.'%';
        for($i =0 ;$i < count($search_keys) ; $i++){
            $whereclause .= " OR user_name LIKE :u".$i." ";
            $params[':u'.$i] = "%".$search_keys[$i]."%";
        }
        
        $users = $db->query("SELECT user_name , id FROM users WHERE user_name LIKE :search_key ".$whereclause,$params);

        return json_encode($users);
      }
   
      public static function getusername($userid){
        global $db;
        $username = $db->query("SELECT user_name FROM users WHERE id = :user_id",array(':user_id' => $userid))[0]['user_name'];
        return '{"name":"'.$username.'"}';
      }
}