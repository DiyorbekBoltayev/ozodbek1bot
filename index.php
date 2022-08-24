<?php
require_once "connect.php";
include 'Telegram.php';

$telegram = new Telegram('5556639565:AAGvOzJy27T4TEizvw958xdidtiyv_xkXRY');

$chat_id = $telegram->ChatID();  // foydalanuvchi ID si
$chat_name = $telegram->FirstName();  // foydalanuvchi nomi
$text = $telegram->Text(); // foydalanuvchi yuborgan text
$data = $telegram->getData();
$message = $data['message'];


$step = "";
$name = $message['from']['first_name'];
$date = date('Y-m-d H:i:s', $message['date']);
$sql = "SELECT chat_id from users WHERE chat_id=$chat_id";
$result = mysqli_query($conn, $sql);

if ($result->num_rows != 0) {
    $sql = "select step from users where chat_id='$chat_id'";
    $result = mysqli_query($conn, $sql);
    $row = $result->fetch_assoc();
    $step = $row['step'];
}

$massa = ["1kg = 25 000 sum", "2kg = 50 000 sum", "3kg = 75 000 sum", "4kg = 100 000 sum"];

if ($text == "/start") {
    showStart();
} elseif ($text == "🍯 Biz haqimizda") {
    showAbout();
} elseif ($text == "🚛 Buyurtma berish") {
    showOrder();
} elseif (in_array($text, $massa)) {
    askContact(); // massa tanlandi
} elseif ($step == "phone") {
    telefonYuborildi();
} elseif ($step == 'location' || $text == "🚘 O'zim boraman") {
    if ($text == "🚘 O'zim boraman") {
        $text = "Bizdan kelib oladi";
    }
    if ($message['location']['latitude'] == "") {
        $satr = "";
        for ($i = 0; $i < strlen($text); $i++) {
            if ($text[$i] != "'") {
                $satr .= $text[$i];
            }
        }
        $sql = "update users set latitude='',longitude='', address='$satr',step='tugadi' where chat_id='$chat_id'";
        mysqli_query($conn, $sql);
        } else {
        $latitude = $message['location']['latitude'];
        $longitude = $message['location']['longitude'];
        $sql = "update users set address='',latitude='$latitude',longitude='$longitude',step='tugadi' where chat_id='$chat_id'";
        mysqli_query($conn, $sql);
    }
    buyurtmaQabulQilindi();
}
elseif ($text=='❌ Buyurtmani bekor qilish'){
    $sql="update users set otmen=1,step='start' where chat_id='$chat_id'";
    mysqli_query($conn,$sql);
    buyurtmaBekorQilindi();
}else{
    $content=[
        'chat_id'=>$chat_id,
        'text'=>"⚠️ Bunday buyruq mavjud emas ! \nIltimos quyidagi tugmalardan birini tanlang 👇"

    ];
    $telegram->sendMessage($content);
}


//Funksiyalar

function showStart()
{
    global $telegram,$chat_id,$conn,$name,$date;

    $sql = "SELECT * from users WHERE chat_id='$chat_id'";
    $result=mysqli_query($conn,$sql);
    if($result->num_rows == 0){
        $sql="insert into users (chat_id,name,created_at,step) values ('$chat_id','$name','$date','start')";
        mysqli_query($conn,$sql);
    }

    $option = [
        [
            $telegram->buildKeyboardButton("🍯 Biz haqimizda")
        ],
        [
            $telegram->buildKeyboardButton("🚛 Buyurtma berish")
        ],

    ];

    $keyb = $telegram->buildKeyBoard($option, $onetime = false, $resize = true);
    $content = [
        'chat_id' => $chat_id,
        'reply_markup' => $keyb,
        'text' => " Assalomu Alaykum biz sof va tabiy asal bilan shug'ullanamiz  ",
    ];

    $telegram->sendMessage($content);

}

function showAbout()
{
    global $chat_id, $telegram;
    $content = [
        'chat_id' => $chat_id,
        'text' => " Biz haqimizda bilib oling <a href='https://telegra.ph/Biz-haqimizda-08-10'>Link</a> ",
        'parse_mode' => "html"
    ];
    $telegram->sendMessage($content);
}

function showOrder()
{
    global $telegram, $chat_id;
    $option = [
        [
            $telegram->buildKeyboardButton("1kg = 25 000 sum"), $telegram->buildKeyboardButton("2kg = 50 000 sum")
        ],

        [
            $telegram->buildKeyboardButton("3kg = 75 000 sum"), $telegram->buildKeyboardButton("4kg = 100 000 sum")
        ],

        [
            $telegram->buildKeyboardButton("Asosiy")
        ]
    ];

    $keyb = $telegram->buildKeyBoard($option, $onetime = false, $resize = true);
    $content = [
        'chat_id' => $chat_id,
        'reply_markup' => $keyb,
        'text' => " Hajm tanlandi endi telefon raqamingizni jo`natsangiz ",
    ];
    $telegram->sendMessage($content);

}

function askContact()
{
    global $telegram,$chat_id,$text,$massa,$conn;

    $index=array_search($text,$massa); // massani qidiradi --> id sini qaytaradi
    $sql="update users set massa='$index',step='phone',otmen='' where chat_id='$chat_id'";
    mysqli_query($conn,$sql);

    $option=[
        [$telegram->buildKeyboardButton('📱 Telefon raqamni yuborish',$request_contact=true)]
    ];
    $keyboard=$telegram->buildKeyBoard($option,$onetime=true,$resize=true);
    $content=[
        'chat_id'=>$chat_id,
        'reply_markup'=>$keyboard,
        'text'=>"✅ Kerakli miqdor tanlandi . Telefon raqamingizni yuboring 👇"
    ];
    $telegram->sendMessage($content);

}

function telefonYuborildi(){
    global $message,$text,$conn,$chat_id;
    if($message['contact']['phone_number'] == ""){
        $phone=substr($text,1);
        if(is_numeric($phone)){
            $sql="update users set phone='$text',step='location' where chat_id='$chat_id'";
            mysqli_query($conn,$sql);
            joylashuvYuborish();
        }else{
            telefonXato();
        }
    } else{
        $t=$message['contact']['phone_number'];
        $sql="update users set phone='$t',step='location' where chat_id='$chat_id'";
        mysqli_query($conn,$sql);
        joylashuvYuborish();
    }

}

function joylashuvYuborish(){
    global $telegram,$chat_id;

    $option=[
        [$telegram->buildKeyboardButton("🔻 Joylashuvni yuborish",$request_contact=false,$request_location=true)],
        [$telegram->buildKeyboardButton("🚘 O'zim boraman")]
    ];
    $keyboard=$telegram->buildKeyBoard($option,$onetime=true,$resize=true);
    $content=[
        'chat_id'=>$chat_id,
        'reply_markup'=>$keyboard,
        'text'=>"  🗺 Urganch tumani bo'ylab yetkazib berish bepul !\n🚛 Yetkazib berish uchun manzilni kiriting yoki joylashuvni yuboring. Istasangiz o'zingiz kelib olib ketishingiz ham mumkin. \n 🏢 Bizning manzil: Urganch tumani Kattabog' mahallasi Ummon ko'chasi 28-uy"
    ];

    $telegram->sendMessage($content);
}

function telefonXato(){
    global $telegram,$chat_id,$conn;

    $sql="update users set step='phone' where chat_id='$chat_id' ";
    mysqli_query($conn,$sql);


    $option=[
        [$telegram->buildKeyboardButton('📱 Telefon raqamni yuborish',$request_contact=true)]
    ];
    $keyboard=$telegram->buildKeyBoard($option,$onetime=true,$resize=true);
    $content=[
        'chat_id'=>$chat_id,
        'reply_markup'=>$keyboard,
        'text'=>"Telefon raqamini kirtishda xatolik , iltimos qaytadan  kiriting, masalan: 883621700"
    ];
    $telegram->sendMessage($content);
}

function buyurtmaQabulQilindi(){
    global $telegram,$chat_id;

    $option=[
        [$telegram->buildKeyboardButton('❌ Buyurtmani bekor qilish')]
    ];
    $keyboard=$telegram->buildKeyBoard($option,$onetime=true,$resize=true);
    $content=[
        'chat_id'=>$chat_id,
        'reply_markup'=>$keyboard,
        'text'=>"  ✅ Buyurtma qabul qilindi.\n☎️ Siz bilan tez orada bog'lanamiz."
    ];

    $telegram->sendMessage($content);

}

function buyurtmaBekorQilindi(){
    global $telegram,$chat_id;
    $option=[
        [$telegram->buildKeyboardButton('📜 Biz haqimizda')],
        [$telegram->buildKeyboardButton('🚛 Buyurtma berish')],
    ];
    $keyboard=$telegram->buildKeyBoard($option, $onetime=false , $resize=true);
    $content=[
        'chat_id'=>$chat_id,
        'reply_markup'=>$keyboard,
        'text'=>"⚠️ Joriy buyurtma bekor qilindi ! \n♻️ Istasangiz yangidan buyurtma qilishingiz mumkin"

    ];
    $telegram->sendMessage($content);
}