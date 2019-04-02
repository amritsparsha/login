<?php
    session_start();
    include("config.php");
    //Check if the user has already tried to login
    //If they have, set the variable $auth_tries to the value of the cookie and get the expire time
    if (isset($_COOKIE['time_limit'])) {
        $num_tries = $_COOKIE['auth_attempts'];
        $expire_time = $_COOKIE['time_limit'];
    } else { //If they haven't, create the cookie with a value of 1, since this is their first attempt
        setcookie('auth_attempts', 1,  time()+900); //Cookie named 'auth_attempts' with a stored value of '1' that expires in 900 seconds from now (15 minutes)
        setcookie('time_limit', time()+900, time()+900);  //Cookie named 'time_limit' that stores the expiration time of the first login attempt
        $num_tries = $_COOKIE['auth_attempts'];  //Value of 1
        $expire_time = $_COOKIE['time_limit']; //Timestamp of 15 minutes from now
    }
    
    //Check if the user has exceeded 3 login attempts in 15 minutes
    //If true, log the current timestamp in the DB and let the user know they are temporarily locked out
    if ($num_tries > 3) {
        $lockout_time = time() + 3600;  //Timestamp of 1 hour from now
        mysqli_query("UPDATE $tbl_name SET MUSE_LOCKED = '$lockout_time' WHERE MUSE_NAME='$myusername'");
        die(print("You have failed to log in 3 times in 15 minutes!  You must wait an hour before attempting to log in again."));
    }
        
    if (isset($_POST['sub'])) {
        $myusername = $_POST['txtusername'];
        $mypassword = $_POST['txtpassword'];
        $name       = stripslashes($myusername);
        $password   = stripslashes($mypassword);
        $myusername = mysqli_real_escape_string($name);
        $mypassword = mysqli_real_escape_string($password);
        $checklock = mysqli_query("SELECT MUSE_LOCKED FROM $tbl_name WHERE MUSE_NAME='$myusername'");
        $checklock_result = mysqlii_result($checklock, 0);  //Returns the timestamp
        if ($checklock_result > time()) {  //If the time restriction is not over
            die(print("You must wait an hour to attempt to log in again!")); 
        } else {
        $sql        = "SELECT * FROM $tbl_name WHERE MUSE_NAME='$myusername' and MUSE_PWD='$mypassword'";
        $result     = mysqlii_query($sql);
        $count      = mysqlii_num_rows($result);
        if ($count == 1) {
            $_SESSION['login'] = "1";
            //Force the cookies to expire, since they have logged in successfully
            setcookie('auth_tries', '', time()-3600);
            setcookie('time_limit', '', time()-3600);
            header("location:Main_Dashboard.php");
        }
		else
		{
		    $_SESSION['error'] = "Incorrect username or password";
                    $num_tries++; //Increment $auth_tries to reflect the failed login attempt
                    setcookie('auth_attempts', $num_tries, $expire_time); //Update your 'auth_attempts' cookie
            header("location:Main_Login.php");
                }
        }
    }
?>
