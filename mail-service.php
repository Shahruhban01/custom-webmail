<?php
$status = "";

if($_SERVER["REQUEST_METHOD"]=="POST"){

$name = $_POST["name"];
$from = $_POST["from"];
$to = $_POST["to"];
$subject = $_POST["subject"];
$message = $_POST["message"];

$headers = "From: $name <$from>\r\n";
$headers .= "Reply-To: $from\r\n";
$headers .= "MIME-Version: 1.0\r\n";

if(isset($_FILES["file"]) && $_FILES["file"]["error"]==0){

$file_tmp = $_FILES["file"]["tmp_name"];
$file_name = $_FILES["file"]["name"];
$file_data = chunk_split(base64_encode(file_get_contents($file_tmp)));

$boundary = md5(time());

$headers .= "Content-Type: multipart/mixed; boundary=\"$boundary\"\r\n";

$body = "--$boundary\r\n";
$body .= "Content-Type:text/plain; charset=UTF-8\r\n\r\n";
$body .= $message."\r\n";

$body .= "--$boundary\r\n";
$body .= "Content-Type: application/octet-stream; name=\"$file_name\"\r\n";
$body .= "Content-Transfer-Encoding: base64\r\n";
$body .= "Content-Disposition: attachment; filename=\"$file_name\"\r\n\r\n";
$body .= $file_data."\r\n";
$body .= "--$boundary--";

$send = mail($to,$subject,$body,$headers);

}else{

$headers .= "Content-Type:text/plain;charset=UTF-8\r\n";
$send = mail($to,$subject,$message,$headers);

}

if($send){
$status="<div class='success'>Email Sent Successfully</div>";
}else{
$status="<div class='error'>Email Failed</div>";
}

}
?>

<!DOCTYPE html>
<html>
<head>

<title>Email Sender Dashboard</title>

<style>

body{
font-family:Arial;
background:#0f172a;
margin:0;
}

.header{
background:#111827;
color:white;
padding:15px;
font-size:22px;
text-align:center;
}

.container{
width:900px;
margin:40px auto;
background:white;
padding:30px;
border-radius:10px;
box-shadow:0 10px 25px rgba(0,0,0,0.2);
}

.grid{
display:grid;
grid-template-columns:1fr 1fr;
gap:15px;
}

input,textarea{
width:100%;
padding:10px;
border:1px solid #ccc;
border-radius:6px;
font-size:14px;
}

textarea{
height:140px;
resize:none;
}

.full{
grid-column:1 / 3;
}

button{
background:#2563eb;
color:white;
border:none;
padding:12px;
font-size:16px;
border-radius:6px;
cursor:pointer;
}

button:hover{
background:#1d4ed8;
}

.success{
background:#d1fae5;
padding:10px;
border-radius:6px;
margin-bottom:15px;
color:#065f46;
}

.error{
background:#fee2e2;
padding:10px;
border-radius:6px;
margin-bottom:15px;
color:#991b1b;
}

.preview{
margin-top:25px;
background:#f3f4f6;
padding:15px;
border-radius:8px;
}

</style>

</head>

<body>

<div class="header">
Professional Email Sender Dashboard
</div>

<div class="container">

<?php echo $status; ?>

<form method="POST" enctype="multipart/form-data">

<div class="grid">

<input type="text" name="name" placeholder="Sender Name" required>

<input type="email" name="from" placeholder="Sender Email" required>

<input type="email" name="to" placeholder="Receiver Email" required>

<input type="text" name="subject" placeholder="Subject" required>

<textarea class="full" name="message" placeholder="Write your email message..." required></textarea>

<input class="full" type="file" name="file">

<button class="full">Send Email</button>

</div>

</form>

</div>

</body>
</html>