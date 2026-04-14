<?php

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function get_pdo(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dbName =  'app';
    $dbUser =  'app';
    $dbPass =  'app';
    $dbPort =   3306;
    $dbHost = 'db';
    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $dbHost, $dbPort, $dbName);

    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    return $pdo;
}

function countries(): array
{
    return [
        'Select Country',
        'Egypt',
        'Saudi Arabia',
        'United Arab Emirates',
        'Jordan',
        'Kuwait',
        'Qatar',
        'Bahrain',
        'Oman',
        'Lebanon',
        'Syria',
        'Iraq',
        'Palestine',
        'Sudan',
        'Libya',
        'Morocco',
        'Algeria',
        'Tunisia',
        'United States',
        'United Kingdom',
        'Canada',
        'Australia',
        'India',
        'Pakistan',
        'Bangladesh',
        'China',
        'Japan',
        'Germany',
        'France',
    ];
}

function skill_options(): array
{
    return ['PHP', 'J2SE', 'MySQL', 'PostgreSQL'];
}

function load_users(): array
{
    $pdo = get_pdo();
    // stmt: statement shorthand
    $stmt = $pdo->query(
        'SELECT id, first_name, last_name, address, country, gender, username, department, created_at, updated_at
         FROM users
         ORDER BY created_at DESC'
    );
    $users = $stmt->fetchAll();

    if ($users === []) {
        return [];
    }

    $userIds = array_column($users, 'id');
    $placeholders = implode(',', array_fill(0, count($userIds), '?'));
    $skillsStmt = $pdo->prepare("SELECT user_id, skill FROM user_skills WHERE user_id IN ($placeholders) ORDER BY id ASC");
    $skillsStmt->execute($userIds);

    $skillsByUser = [];
    foreach ($skillsStmt->fetchAll() as $row) {
        $skillsByUser[$row['user_id']][] = $row['skill'];
    }

    foreach ($users as &$user) {
        $user['skills'] = $skillsByUser[$user['id']] ?? [];
    }
    unset($user);

    return $users;
}

function make_user_id(): string
{
    return uniqid('usr_', true);
}

function add_user(array $user): string
{
    $pdo = get_pdo();
    $id = make_user_id();
    $skills = array_values(array_intersect(skill_options(), (array) ($user['skills'] ?? [])));

    try {
        // a transaction to make sure that both user and skills are added together
        $pdo->beginTransaction();

        $stmt = $pdo->prepare(
            'INSERT INTO users (id, first_name, last_name, address, country, gender, username, department)
             VALUES (:id, :first_name, :last_name, :address, :country, :gender, :username, :department)'
        );
        $stmt->execute([
            'id' => $id,
            'first_name' => $user['first_name'] ?? '',
            'last_name' => $user['last_name'] ?? '',
            'address' => $user['address'] ?? '',
            'country' => $user['country'] ?? '',
            'gender' => $user['gender'] ?? '',
            'username' => $user['username'] ?? '',
            'department' => $user['department'] ?? '',
        ]);

        if ($skills !== []) {
            $skillsStmt = $pdo->prepare('INSERT INTO user_skills (user_id, skill) VALUES (:user_id, :skill)');
            foreach ($skills as $skill) {
                $skillsStmt->execute([
                    'user_id' => $id,
                    'skill' => $skill,
                ]);
            }
        }

        $pdo->commit();
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        if ($e->getCode() === '23000') {
            throw new InvalidArgumentException('Username already exists.', 0, $e);
        }

        throw $e;
    }

    return $id;
}

function find_user(string $id): ?array
{
    $pdo = get_pdo();
    $stmt = $pdo->prepare(
        'SELECT id, first_name, last_name, address, country, gender, username, department, created_at, updated_at
         FROM users WHERE id = :id LIMIT 1'
    );
    $stmt->execute(['id' => $id]);
    $user = $stmt->fetch();

    if ($user === false) {
        return null;
    }

    $skillsStmt = $pdo->prepare('SELECT skill FROM user_skills WHERE user_id = :user_id ORDER BY id ASC');
    $skillsStmt->execute(['user_id' => $id]);
    $user['skills'] = array_column($skillsStmt->fetchAll(), 'skill');

    return $user;
}

function update_user(string $id, array $updated): bool
{
    $pdo = get_pdo();
    $skills = array_values(array_intersect(skill_options(), (array) ($updated['skills'] ?? [])));

    try {
        // same same but different
        $pdo->beginTransaction();

        $existsStmt = $pdo->prepare('SELECT id FROM users WHERE id = :id LIMIT 1');
        $existsStmt->execute(['id' => $id]);
        if ($existsStmt->fetch() === false) {
            $pdo->rollBack();
            return false;
        }

        $stmt = $pdo->prepare(
            'UPDATE users
             SET first_name = :first_name,
                 last_name = :last_name,
                 address = :address,
                 country = :country,
                 gender = :gender,
                 username = :username,
                 department = :department,
                 updated_at = NOW()
             WHERE id = :id'
        );
        $stmt->execute([
            'first_name' => $updated['first_name'] ?? '',
            'last_name' => $updated['last_name'] ?? '',
            'address' => $updated['address'] ?? '',
            'country' => $updated['country'] ?? '',
            'gender' => $updated['gender'] ?? '',
            'username' => $updated['username'] ?? '',
            'department' => $updated['department'] ?? '',
            'id' => $id,
        ]);

        $deleteStmt = $pdo->prepare('DELETE FROM user_skills WHERE user_id = :user_id');
        $deleteStmt->execute(['user_id' => $id]);

        if ($skills !== []) {
            $insertSkillStmt = $pdo->prepare('INSERT INTO user_skills (user_id, skill) VALUES (:user_id, :skill)');
            foreach ($skills as $skill) {
                $insertSkillStmt->execute([
                    'user_id' => $id,
                    'skill' => $skill,
                ]);
            }
        }

        $pdo->commit();
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        if ($e->getCode() === '23000') {
            throw new InvalidArgumentException('Username already exists.', 0, $e);
        }

        throw $e;
    }

    return true;
}

function delete_user(string $id): bool
{
    $pdo = get_pdo();
    $stmt = $pdo->prepare('DELETE FROM users WHERE id = :id');
    $stmt->execute(['id' => $id]);

    return $stmt->rowCount() > 0;
}
