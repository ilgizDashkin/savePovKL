<?php header('Access-Control-Allow-Origin: *'); //разрешаем кроссдоменные запросы CORS  
?> 
<?php
require_once 'connection.php'; // подключаем скрипт
//изменил savepovkl чтоб грузить blob файлы изображений
if (isset($_POST['name'])) {
    $json = array(); //переменная для ответа от сервера
    // подключаемся к серверу
    $link = mysqli_connect($host, $user, $password, $database)
        or die("Ошибка " . mysqli_error($link));

    // экранирования символов для mysql
    $name = htmlentities(mysqli_real_escape_string($link, $_POST['name']));
    $zamer = htmlentities(mysqli_real_escape_string($link, $_POST['zamer']));
    $otkuda = htmlentities(mysqli_real_escape_string($link, $_POST['otkuda']));
    $priv = htmlentities(mysqli_real_escape_string($link, $_POST['priv']));
    $dlinna = htmlentities(mysqli_real_escape_string($link, $_POST['dlinna']));
    $kto = htmlentities(mysqli_real_escape_string($link, $_POST['kto']));

    //file
    $json['file1exif'] =  "Не найдено данных заголовка в фото1 . " ;
    $json['file1geo'] = "Нет геотегов в фото1. ";
    
    if (!empty($_POST['foto1'])) {
        // использум этот способ если посылаем изображение в виде base64 строки
        $name_foto1 = str_replace(' ', '', 'foto/' . date("Y-m-d-H-i") . $_POST['name'] . '_foto1.jpg'); //место где сохраним
        file_put_contents($name_foto1, file_get_contents($_POST['foto1']));
        $json['file1'] = "Файл 1 загружен. ";
    } else {
        $json['file1'] = "Файл 1 не загружен. ";
    }

    if (!empty($_POST['foto2'])) {
        // использум этот способ если посылаем изображение в виде base64 строки
        $name_foto2 = str_replace(' ', '', 'foto/' . date("Y-m-d-H-i") . $_POST['name'] . '_foto2.jpg'); //место где сохраним
        file_put_contents($name_foto2, file_get_contents($_POST['foto2']));
        $json['file2'] = "Файл 2 загружен. ";
    } else {
        $json['file2'] = "Файл 2 не загружен. ";
    }

    if (!empty($_POST['foto3'])) {
        $name_foto3 = str_replace(' ', '', 'foto/' . date("Y-m-d-H-i") . $_POST['name'] . '_foto3.jpg');
        file_put_contents($name_foto3, file_get_contents($_POST['foto3']));
        $json['file3'] = "Файл 3 загружен. ";
    } else {
        $json['file3'] = "Файл 3 не загружен. ";
    }

    //fileend 
    ini_set('display_errors', 'Off'); //отключить сообщение об ошибках
    // создание строки запроса
    $query = "INSERT INTO povkl VALUES(NULL,'$name','$date','$zamer','$otkuda','$priv','$dlinna','$gps','$kto','$name_foto1'"
        . ",'$name_foto2','$name_foto3')";
    ini_set('display_errors', 'On');  //включить сообщение об ошибках      

    // выполняем запрос
    $result = mysqli_query($link, $query) or die("Ошибка " . mysqli_error($link));
    if ($result) {
        // echo "Спасибо ваша привязка КЛ $name добавлена! ";

        $json['name'] = $name;
        echo json_encode($json); //отправляем ответ от сервер в json
    }
    // закрываем подключение
    mysqli_close($link);
}
?>


