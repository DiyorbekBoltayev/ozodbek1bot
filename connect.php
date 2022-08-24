<?php
//mysql://b9953d575bd1d4:52c1bcc5@us-cdbr-east-06.cleardb.net/heroku_1d7209728fd0f15?reconnect=true
$servername = "us-cdbr-east-06.cleardb.net";
$username = "b9953d575bd1d4";
$password = "52c1bcc5";
$db="heroku_1d7209728fd0f15";
$conn = mysqli_connect("$servername", "$username", "$password","$db");
mysqli_set_charset($conn,'utf8');

//$sql="select * from users";
//$result=mysqli_query($conn,$sql);
//while ($row=$result->fetch_assoc()){
//    var_dump($row);
//}





//// Xatolikni tekshirish uchun

$e_message = "";
try {


} catch (\Exception $e) {
    $e_message .= $e->getMessage();
    $e_message .= $e->getLine();
    $e_message .= $e->getFile();
    $telegram->sendMessage(['chat_id' => $chat_id, 'text' => $e_message]);

} catch (Throwable $e) {
    $e_message .= $e->getMessage();
    $e_message .= $e->getLine();
    $e_message .= $e->getFile();
    $telegram->sendMessage(['chat_id' => $chat_id, 'text' => $e_message]);

}