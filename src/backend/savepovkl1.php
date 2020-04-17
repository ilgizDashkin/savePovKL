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
    if ($_FILES && $_FILES['foto1']['error'] == UPLOAD_ERR_OK && $_FILES['foto1']['name'] != '') {
        $name_foto1 = 'foto/' . $_FILES['foto1']['name'];
        move_uploaded_file($_FILES['foto1']['tmp_name'], $name_foto1);
        // echo "Файл 1 загружен. ";
        $json['file1'] = "Файл 1 загружен. ";
        //поиск даты в фото
        $filename = $name_foto1;
        $exif = exif_read_data($filename, 'IFD0');
        // echo $exif===false ? "Не найдено данных заголовка в фото1 . " : "Изображение содержит заголовки в фото1 . ";
        $json['file1exif'] = ($exif === false ? "Не найдено данных заголовка в фото1 . " : "Изображение содержит заголовки в фото1 . ");
        $edate = $exif['DateTime'];
        // echo "Дата из фото $edate. ";
        $date = substr($edate, 0, 10); // возвращает "10 первых символов даты"
        //это скрипт по извлечению gps
        // Имя файла для обработки
        $file_path = $filename;
        // Массив с GPS-данными
        $gps_data = array();
        $f = fopen($file_path, 'r');
        $tmp = fread($f, 2);
        if ($tmp == chr(0xFF) . chr(0xD8)) {
            $section_id_stop = array(0xFFD8, 0xFFDB, 0xFFC4, 0xFFDD, 0xFFC0, 0xFFDA, 0xFFD9);
            while (!feof($f)) {
                $tmp = unpack('n', fread($f, 2));
                $section_id = $tmp[1];
                $tmp = unpack('n', fread($f, 2));
                $section_length = $tmp[1];
                // Началась секция данных, заканчиваем поиск
                if (in_array($section_id, $section_id_stop)) {
                    break;
                }

                // Найдена EXIF-секция
                if ($section_id == 0xFFE1) {
                    $exif = fread($f, ($section_length - 2));

                    // Это действительно секция EXIF?
                    if (substr($exif, 0, 4) == 'Exif') {
                        // Определить порядок следования байт
                        switch (substr($exif, 6, 2)) {
                            case 'MM': {
                                    $is_motorola = true;
                                    $mask1 = 'n';
                                    $mask2 = 'N';
                                    break;
                                }
                            case 'II': {
                                    $is_motorola = false;
                                    $mask1 = 'v';
                                    $mask2 = 'V';
                                    break;
                                }
                        }
                        // Количество тегов
                        $tmp = unpack($mask2, substr($exif, 10, 4));
                        $offset_tags = $tmp[1];
                        $tmp = unpack($mask1, substr($exif, 14, 2));
                        $num_of_tags = $tmp[1];

                        if ($num_of_tags == 0) {
                            return true;
                        }

                        $offset = $offset_tags + 8;

                        // Поискать тег GPSInfo
                        for ($i = 0; $i < $num_of_tags; $i++) {
                            $tmp = unpack($mask1, substr($exif, $offset, 2));
                            $tag_id = $tmp[1];
                            $tmp = unpack($mask2, substr($exif, $offset + 8, 4));
                            $value = $tmp[1];

                            $offset += 12;

                            // GPSInfo
                            if ($tag_id == 0x8825) {
                                $gps_offset = $value + 6;
                                // Количество GPS-тегов
                                $tmp = unpack($mask1, substr($exif, $gps_offset, 2));
                                $num_of_gps_tags = $tmp[1];

                                $offset = $gps_offset + 2;

                                if ($num_of_gps_tags > 0) {
                                    // Обработка GPS-тегов
                                    for ($i = 0; $i < $num_of_gps_tags; $i++) {
                                        $tmp = unpack($mask1, substr($exif, $offset, 2));
                                        $tag_id = $tmp[1];
                                        $tmp = unpack($mask2, substr($exif, $offset + 8, 4));
                                        $value = $tmp[1];

                                        // GPSLatitudeRef или GPSLongitudeRef
                                        if ($tag_id == 0x0001 || $tag_id == 0x0003) {
                                            $tmp = unpack('V', substr($exif, $offset + 8, 4));
                                            $value = $tmp[1];
                                            if ($value != 0) {
                                                if ($tag_id == 0x0001) {
                                                    $gps_data['GPSLatitudeRef'] = chr($value);
                                                } else {
                                                    $gps_data['GPSLongitudeRef'] = chr($value);
                                                }
                                            }
                                        }
                                        // GPSLatitude или GPSLongitude
                                        if ($tag_id == 0x0002 || $tag_id == 0x0004) {
                                            $rational_offset = $value + 6;
                                            $tmp = unpack($mask2, substr($exif, $rational_offset + 4 * 0, 4));
                                            $val1 = $tmp[1];
                                            $tmp = unpack($mask2, substr($exif, $rational_offset + 4 * 1, 4));
                                            $div1 = $tmp[1];
                                            $tmp = unpack($mask2, substr($exif, $rational_offset + 4 * 2, 4));
                                            $val2 = $tmp[1];
                                            $tmp = unpack($mask2, substr($exif, $rational_offset + 4 * 3, 4));
                                            $div2 = $tmp[1];
                                            $tmp = unpack($mask2, substr($exif, $rational_offset + 4 * 4, 4));
                                            $val3 = $tmp[1];
                                            $tmp = unpack($mask2, substr($exif, $rational_offset + 4 * 5, 4));
                                            $div3 = $tmp[1];
                                            if ($div1 != 0 && $div2 != 0 && $div3 != 0) {
                                                $tmp = round(($val1 / $div1 + $val2 / $div2 / 60 + $val3 / $div3 / 3600), 6);
                                                if ($tag_id == 0x0002) {
                                                    $gps_data['GPSLatitude'] = $tmp;
                                                } else {
                                                    $gps_data['GPSLongitude'] = $tmp;
                                                }
                                            }
                                        }
                                        // GPSSatellites
                                        if ($tag_id == 0x0008) {
                                            $tmp = intval(substr($exif, $offset + 8, 4));
                                            if ($tmp > 0) {
                                                $gps_data['GPSSatellites'] = $tmp;
                                            }
                                        }

                                        $offset += 12;
                                    }
                                }
                                break;
                            }
                        }
                    }
                } else {
                    // Пропустить секцию
                    fseek($f, ($section_length - 2), SEEK_CUR);
                }
                // Тег GPSInfo найден
                if (count($gps_data) != 0) {
                    break;
                }
            }
        }
        fclose($f);
        // Данные GPS
        if (!$gps_data['GPSLatitude'] == "") {
            $text_coordinate = $gps_data['GPSLatitude'] . "," . $gps_data['GPSLongitude'];
            $gps = $text_coordinate;
            //   echo "Координаты $gps . ";
            $json['file1geo'] = "Координаты $gps . ";
        }
        //  else {echo "Нет геотегов в фото $gps . ";}
        else {
            $json['file1geo'] = "Нет геотегов в фото $gps . ";
        }
        //конец скрипта по извлечению gps
    } else {
        $json['file1'] = "Файл 1 не загружен. ";
        $json['file1geo'] = "Нет геотегов в фото1. ";
    }

    //   if ($_FILES && $_FILES['foto2']['error']== UPLOAD_ERR_OK && $_FILES['foto2']['name']!= '')
    // {
    // это если присылаем в виде файла изображения
    //     $name_foto2 = 'foto/'.$_FILES['foto2']['name'];
    //     move_uploaded_file($_FILES['foto2']['tmp_name'], $name_foto2);
    //     // echo "Файл 2 загружен. ";
    //     $json['file2']="Файл 2 загружен. ";
    // } 
    //   if ($_FILES && $_FILES['foto3']['error']== UPLOAD_ERR_OK && $_FILES['foto3']['name']!= '')
    // {
    // это если присылаем в виде файла изображения
    //     $name_foto3 = 'foto/'.$_FILES['foto3']['name'];
    //     move_uploaded_file($_FILES['foto3']['tmp_name'], $name_foto3);
    //     // echo "Файл 3 загружен. ";
    //     $json['file3']="Файл 3 загружен. ";
    // } 

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


