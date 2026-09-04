<!DOCTYPE html>
<html>

<head>
    <title>Forget Password Otp - Toto Ride</title>
</head>

<body>
    <p>Dear {{ $user->name }},</p>
    <p><strong>Forget Password otp:</strong></p>

    <p><strong>Email:</strong> {{ $user->email }}</p>
    <p><strong>Otp:</strong> {{ $user->otp }}</p>

    <p>Use these otp to change the password <b>dont share with anyone</b> </p>
    <p>Thank you for choosing Toto Ride Platform.</p>

    <p>Regards,<br>{{ $user->name }}</p>
</body>

</html>