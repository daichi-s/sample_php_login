<?php

class UserController
{
    const DSN = 'mysql:dbname=db;host=mysql';
    const DB_USER = 'docker';
    const DB_PASSWORD = 'docker';

    public $pdo;

    public function __construct()
    {
        session_start();
        $this->pdo = new PDO(self::DSN, self::DB_USER, self::DB_PASSWORD);
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public function login()
    {
        $stmt = $this->pdo->prepare(
            'select * from user where email = :email'
        );
        $stmt->bindParam(':email', $_REQUEST['email']);
        $stmt->execute();
        $loginUser = $stmt->fetch(PDO::FETCH_ASSOC);

        // 該当ログインユーザーが存在しない場合
        if (!$loginUser) {
            http_response_code(403);
            return;
        }
        // パスワードが一致しない場合
        if (!password_verify($_REQUEST['password'], $loginUser['password'])) {
            http_response_code(403);
            return;
        }

        $_SESSION['userId'] = $loginUser['id'];
        $_SESSION['userName'] = $loginUser['name'];
        session_regenerate_id(true);
        header('Location: /');
        exit;
    }

    public function logout()
    {
        session_destroy();
        header('Location: /');
        exit;
    }

    public function regist()
    {
        try {
            $this->pdo->beginTransaction();

            $stmt = $this->pdo->prepare(
                "insert into user (name, email, password) values (:name, :email, :password)"
            );
            $stmt->bindParam(':name', $_REQUEST['name']);
            $stmt->bindParam(':email', $_REQUEST['email']);
            $stmt->bindParam(':password', password_hash($_REQUEST['password'], PASSWORD_DEFAULT));
            $stmt->execute();

            $this->pdo->commit();
        } catch (PDOException $e) {
            $this->pdo->rollback();
            throw $e->getMessage();
        }
    }
}

$user = new UserController();
if ($_REQUEST['form'] === 'login') {
    $user->login();
}
if ($_REQUEST['form'] === 'logout') {
    $user->logout();
}
if ($_REQUEST['form'] === 'regist') {
    $user->regist();
}
?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>lesson</title>
</head>

<body>
    <?php if ($_SESSION['userId']) : ?>

        <h2>ログイン完了</h2>
        <div>
            <div>アカウント名：<?= $_SESSION['userName']; ?></div>
        </div>
        <form action="" method="post">
            <button type="submit">ログアウト</button>
            <input type="hidden" name="form" value="logout">
        </form>

    <?php else : ?>

        <h2>ログイン</h2>
        <form action="" method="post">
            <div>
                <?php if (http_response_code() === 403) : ?>
                    <p style="color: red;">ユーザ名またはパスワードが違います</p>
                <?php endif; ?>
                <div>
                    <label for="">メールアドレス</label>
                    <input type="email" name="email">
                </div>
                <div>
                    <label for="">パスワード</label>
                    <input type="password" name="password">
                </div>
                <div>
                    <button type="submit">ログイン</button>
                    <input type="hidden" name="form" value="login">
                </div>
            </div>
        </form>

        <hr style="margin: 50px 0px;">

        <h2>アカウント登録</h2>
        <form action="" method="post">
            <div>
                <div>
                    <label for="name">名前</label>
                    <input type="text" name="name">
                </div>

                <div>
                    <label for="email">メールアドレス</label>
                    <input type="email" name="email">
                </div>
                <div>
                    <label for="password">パスワード</label>
                    <input type="password" name="password">
                </div>
                <div>
                    <button type="submit">新規登録</button>
                    <input type="hidden" name="form" value="regist">
                </div>
            </div>
        </form>

    <?php endif; ?>
</body>

</html>