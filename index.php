<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-alpha.4/css/bootstrap.min.css" integrity="2hfp1SzUoho7/TsGGGDaFdsuuDL0LX2hnUp6VkX3CUQ2K4K+xjboZdsXyp4oUHZj" crossorigin="anonymous">
    <title>Document</title>
</head>
<body>
   
    <?php
    // Первая часть
        
    // удаляем базу данных, если она есть
    if (file_exists('myfootball.sqlite3')) unlink('myfootball.sqlite3');      
    // Создадим новую базу данных в каталоге проекта
    $db = new SQLite3('myfootball.sqlite3');
    if(!$db){
        echo $db->lastErrorMsg();
    }
    else
    {
        // создаем таблицу для хранения данных с сайта
        $db->exec('CREATE TABLE matches (match STRING, href STRING, result STRING)');    
        // парсинг ресурса
        $doc = new DOMDocument();
        libxml_use_internal_errors(true);
        $doc->loadHTMLFile("https://www.marathonbet.ru/su/events.htm?id=11");
        // получаем все ссылки
        $tags = $doc->getElementsByTagName('a');

        $i = 0; // специальный счетчик, для ограничения количества закачиваемых статей

        // загружаем в базу только ссылки с результатами матчей        
        foreach ($tags as $tag) 
        {             
            $hr = $tag->getAttribute('href');
            // выбираем только те ссылки, где есть 'vs'
            if (stristr($hr, 'vs') !== FALSE)
            {
                // для сокращения времени отладки ограничим кол-во скачиваемых статей шестью
                $i++;
                if ($i>6) break;
                // получаем урл статьи
                (string)$second = $tag->getAttribute('href');
                // берем с конца урла наименования соперников
                preg_match('/([^\/]*)$/',$second,$opponent);
                (string)$first = $opponent[0];
                // добавляем к ссылке домен
                $second = 'https://www.marathonbet.ru'.$second;

                // Вторая часть
                
                // создаем DOM документ для работы со страницей матча
                $do = new DOMDocument();
                // закачиваем контент
                $do->loadHTMLFile($second);
                // готовим и исполняем запрос на поиск на странице контента класса category-container
                $xpath = new DOMXpath($do);
                $expression = './/div[contains(concat(" ", normalize-space(@class), " "), " category-container ")]';                
                $htmldom = $xpath->evaluate($expression);        
                $xml = $htmldom[0]->ownerDocument->saveXML();  
                $new = htmlspecialchars($xml, ENT_QUOTES);
                // записываем спарсенный контент в базу
                $q = "INSERT INTO matches (match, href, result) VALUES('$first','$second', '$new')";
                //записываем в базу данных новую пару
                $db->exec($q);                
            }
        }
        // удаляем дубликаты из базы
        $q = "delete from matches where rowid not in (select max(rowid) from matches group by href)";
        $db->exec($q);
        // выводим базу
        echo '<table class="table table-inverse">
            <thead>
                <tr>
                <th>#</th>
                <th>Match</th>
                <th>Href</th>
                <th>Result</th>
                </tr>
            </thead>
            <tbody>';
        $query = "SELECT match, href, result FROM matches";
        $results = $db->query($query);
        echo '<ul>';
        $i = 0;
        while ($row = $results->fetchArray(SQLITE3_ASSOC)) {
            $i++;
            echo '<tr><th scope="row">'.$i.'</th><td>'.$row['match'].'</td><td>'.$row['href'].'</td><td>'.htmlspecialchars_decode($row['result']).'</td></tr>';
        }
        echo '</tbody></table>';

    }
    
    ?> 
        
   
</body>
</html>