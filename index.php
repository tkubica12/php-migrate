<?php
    $servername = getenv("MYSQL_HOST");
    $username = getenv("MYSQL_USERNAME");
    $password = getenv("MYSQL_PASSWORD");
    $dbname = getenv("MYSQL_DB");
    $dsn = "mysql:host=$servername;dbname=$dbname";
    $options = array(
        PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
        PDO::MYSQL_ATTR_SSL_CA => '/cacert.pem',
        PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
    );
    $pdo = new PDO($dsn, $username, $password, $options);

    if(isset($_POST['submit']) ){
        $name = $_POST['name'];
        $sth = $pdo->prepare("INSERT INTO todos (name) VALUES (:name)");
        $sth->bindValue(':name', $name, PDO::PARAM_STR);
        $sth->execute();
    }elseif(isset($_POST['delete'])){
        $id = $_POST['id'];
        $sth = $pdo->prepare("delete from todos where id = :id");
        $sth->bindValue(':id', $id, PDO::PARAM_INT);
        $sth->execute();
    }
?>

<!DOCTYPE HTML>
<html lang="ja">
<head>
    <title>Todo List</title>
    <link rel="stylesheet" href="//fonts.googleapis.com/css?family=Roboto:300,300italic,700,700italic">
    <link rel="stylesheet" href="//cdn.rawgit.com/necolas/normalize.css/master/normalize.css">
    <link rel="stylesheet" href="//cdn.rawgit.com/milligram/milligram/master/dist/milligram.min.css">
</head>

<body class="container">
    <h1>Todo List</h1>
    <form method="post" action="">
        <input type="text" name="name" value="">
        <input type="submit" name="submit" value="Add">
    </form>
    <h2>Current Todos</h2>
    <table class="table table-striped">
        <therad><th>Task</th><th></th></therad>
        <tbody>
<?php
    $sth = $pdo->prepare("SELECT * FROM todos ORDER BY id DESC");
    $sth->execute();
    
    foreach($sth as $row) {
?>
            <tr>
                <td><?= htmlspecialchars($row['name']) ?></td>
                <td>
                    <form method="POST">
                        <button type="submit" name="delete">Delete</button>
                        <input type="hidden" name="id" value="<?= $row['id'] ?>">
                        <input type="hidden" name="delete" value="true">
                    </form>
                </td>
            </tr>
<?php
    }
?>
        </tbody>
    </table>
</body>
</html>