<?php
require_once 'connect.php';
include 'Telegram.php';
$telegram = new Telegram('5765473868:AAFTjhnNlQFCl5714oJ2Sc5_FLwoNhnoAP0');
$chat_id=$telegram->ChatID();
$text=$telegram->Text();
$data=$telegram->getData();
$message=$data['message'];
//$ma=json_encode($message,JSON_PRETTY_PRINT);
//$telegram->sendMessage(['chat_id'=>$chat_id,'text'=>$ma]);
$name=$message['from']['first_name'];
$date=date('Y-m-d H:i:s',$message['date']);
$step="";
$sql = "SELECT chat_id from users WHERE chat_id=$chat_id";
$result=mysqli_query($conn,$sql);
if($result->num_rows != 0){
    $sql="select step from users where chat_id='$chat_id'";
    $result=mysqli_query($conn, $sql);
    $row = $result->fetch_assoc();
    $step=$row['step'];
}
$massa=[
    '0.5 kilogramm - 💵 50 000 so`m',
    '1 kilogramm - 💵 90 000 so`m',
    '2 kilogramm - 💵 170 000 so`m',
    '3 kilogramm - 💵 250 000 so`m',
    '5 kilogramm - 💵 400 000 so`m',
    '10 kilogramm - 💵 750 000 so`m'
];

if($text=='/start'){
    start();
}elseif ($text=='📜 Biz haqimizda'){
    bizHaqimizda();
}
elseif ($text=='🚛 Buyurtma berish'){
    buyurtmaBerish();
}
elseif ($text==$massa[0]
    || $text==$massa[1]
    || $text==$massa[2]
    || $text==$massa[3]
    || $text==$massa[4]
    || $text==$massa[5]){
    massaTanlandi();
}
elseif ($step=="phone"){
    telefonYuborildi();
}
elseif ($step=='location' || $text=="🚘 O'zim boraman"){
    if($text=="🚘 O'zim boraman"){
        $text="Bizdan kelib oladi";
    }
    if($message['location']['latitude']==""){
        $satr="";
        for($i=0;$i<strlen($text);$i++){
            if($text[$i] != "'"){
                $satr.=$text[$i];
            }
        }
        $sql="update users set latitude='',longitude='', address='$satr',step='tugadi' where chat_id='$chat_id'";
        mysqli_query($conn,$sql);
    }else{
        $latitude=$message['location']['latitude'];
        $longitude=$message['location']['longitude'];
        $sql="update users set address='',latitude='$latitude',longitude='$longitude',step='tugadi' where chat_id='$chat_id'";
        mysqli_query($conn,$sql);
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


function start(){
    global $telegram,$chat_id,$conn,$name,$date;
    $sql = "SELECT * from users WHERE chat_id='$chat_id'";
    $result=mysqli_query($conn,$sql);
    if($result->num_rows == 0){
        $sql="insert into users (chat_id,name,created_at,step) values ('$chat_id','$name','$date','start')";
        mysqli_query($conn,$sql);
    }


    $option=[
        [$telegram->buildKeyboardButton('📜 Biz haqimizda')],
        [$telegram->buildKeyboardButton('🚛 Buyurtma berish')],
    ];
    $keyboard=$telegram->buildKeyBoard($option, $onetime=false , $resize=true);
    $content=[
        'chat_id'=>$chat_id,
        'reply_markup'=>$keyboard,
        'text'=>"Assalomu alaykum '$name', Botimizga xush kelibsiz !  Bot orqali masofadan turib 🍯 asal buyurtma qilishingiz mumkin !"

    ];
    $telegram->sendMessage($content);
}
function bizHaqimizda(){
    global $telegram,$chat_id;
    $content=[
        'chat_id'=>$chat_id,
        'text'=>"Biz haqimizda bilib oling <a href='https://telegra.ph/Biz-haqimizda-08-10'>Link</a> "
        ,'parse_mode'=>'html'
    ];
    $telegram->sendMessage($content);
}

function buyurtmaBerish(){
    global $telegram,$chat_id,$massa;
    $option=[
        [$telegram->buildKeyboardButton($massa[0])],
        [$telegram->buildKeyboardButton( $massa[1])],
        [$telegram->buildKeyboardButton( $massa[2])],
        [$telegram->buildKeyboardButton( $massa[3])],
        [$telegram->buildKeyboardButton( $massa[4])],
        [$telegram->buildKeyboardButton($massa[5])],
    ];
    $keyboard=$telegram->buildKeyBoard($option,$onetime=true,$resize=true);
    $content=[
        'chat_id'=>$chat_id,
        'reply_markup'=>$keyboard,
        'text'=>"Kerakli miqdorni tanlang : "

    ];
    $telegram->sendMessage($content);
}

function massaTanlandi(){
    global $telegram,$chat_id,$text,$massa,$conn;
    $index=array_search($text,$massa);
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
    $sql="update users set step='phone' where chat_id='$chat_id'";
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

