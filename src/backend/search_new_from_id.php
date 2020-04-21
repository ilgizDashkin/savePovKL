<?php header('Access-Control-Allow-Origin: *'); //разрешаем кроссдоменные запросы CORS  
?>
<!doctype html>
<html lang="ru">
<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
    <title>место повреждения</title>
</head>
<body class=" bg-dark text-white">
    <div class='container p-2'>
        <input type="button" class="btn btn-info btn-lg btn-block" value='вернуться назад' onclick="window.history.back()" />
    </div>

    <?php
    require_once 'connection.php'; // подключаем скрипт
    //принимаем гет запросы номер id и возвращаем место повреждения
    function search($query, $host, $user, $password, $database)
    {
        $link = mysqli_connect($host, $user, $password, $database)
            or die("Ошибка " . mysqli_error($link));

        $query = trim($query);
        $query = htmlspecialchars($query);

        if (!empty($query)) {
            // var_dump($query);
            $q = "SELECT * FROM `povkl` WHERE `id` LIKE '%$query%'";
            $result = mysqli_query($link, $q) or die("Ошибка " . mysqli_error($link));
            //   var_dump($result);
            if ($result) {
                $row = mysqli_fetch_assoc($result);
                $text = "<div class='container p-2'><div>";
                do {
                    // Делаем запрос, получающий ссылки на статьи
                    $q1 = "SELECT * FROM `povkl` WHERE `id` = '$row[id]'";
                    $result1 = mysqli_query($link, $q1) or die("Ошибка " . mysqli_error($link));
                    if ($result1) {
                        $row1 = mysqli_fetch_assoc($result1);
                    }
                    //moi
                    //moi проверяет есть ли картинка в ответе из базы
                    // var_dump($row1['foto1']); 
                    if (!$row1['foto1']) {
                        $text_foto1 = '<tr><td></td><td>нет фото';
                    } else {
                        $text_foto1 = '<tr><td></td><td><a href="' . $row1['foto1'] . '"><img src="' . $row1['foto1'] . '"width="200"  height="200"> </a>';
                    }

                    if (!$row1['foto2']) {
                        $text_foto2 = '<tr><td></td><td>нет фото';
                    } else {
                        $text_foto2 = '<tr><td></td><td><a href="' . $row1['foto2'] . '"><img src="' . $row1['foto2'] . '"width="200"  height="200"></a>';
                    }

                    if (!$row1['foto3']) {
                        $text_foto3 = '<tr><td></td><td>нет фото';
                    } else {
                        $text_foto3 = '<tr><td></td><td><a href="' . $row1['foto3'] . '"><img src="' . $row1['foto3'] . '"width="200"  height="200"></a>';
                    }
                    //moi конец фото проверки
                    // проверяет есть ли координаты в ответе из базы
                    // var_dump($row['gps']); 
                    if (!$row['gps']) {
                        $text_gps = '<tr><td></td><td>нет координат';
                    } else {
                        $text_gps = '<tr><td>gps</td><td><a href="https://maps.google.com/?hl=ru&q=' . $row['gps'] . '">приблизительное место </a>';
                    }
                    //end проверяет есть ли координаты в ответе из базы
                    //moi
                    $text .= "<div class='table-responsive alert alert-success'>
                    <table class='table table-bordered '>   
                        <tr><td>имя</td><td> " . $row['name']
                        . '<tr><td>дата</td><td> ' . $row['date'] .
                        '<tr><td>замер</td><td>  ' . $row['zamer'] .
                        '<tr><td>откуда замер</td><td>  ' . $row['otkuda'] .
                        '<tr><td> привязка </td><td>  ' . $row['priv'] .
                        '<tr><td> вся длинна </td><td>  ' . $row['dlinna'] .
                        $text_gps .
                        '<tr><td> кто нашёл</td><td>  ' . $row['kto'] .
                        $text_foto1 . $text_foto2 . $text_foto3 .
                        '</td></tr>
                    </table></div><br>';
                } while ($row = mysqli_fetch_assoc($result));
            } else {
                $text = '<p>По вашему запросу ничего не найдено.</p>';
            }
        }
        return $text;
    }

    if (!empty($_GET['query'])) {
        $search_result = search($_GET['query'], $host, $user, $password, $database);
        echo $search_result;
    }
    ?>
    <!-- Optional JavaScript -->
    <!-- jQuery first, then Popper.js, then Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>
</body>
</html>