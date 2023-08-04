<!DOCTYPE html>
<html>

<head>
    <title>Reset Password</title>
</head>

<body>

  <p> Hi {{$data['userName']}}, You recently requested to reset the password. <br> Click the button below to proceed. If you did not request a password reset, please ignore this email or reply to let us know.</p> 
 <a href="{{$data['url']}}" style="text-decoration: none;padding:10px;color:#375C58">Click Here</a> 


</body>

</html>