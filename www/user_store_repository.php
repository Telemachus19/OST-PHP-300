<?php

function load_users(): array
{
    $pdo = get_pdo();
    $stmt = $pdo->query(
        'SELECT u.id,
                u.first_name,
                u.last_name,
                u.address,
                c.name AS country,
                u.gender,
                u.username,
                d.name AS department,
                u.profile_picture_path,
                u.created_at,
                u.updated_at
         FROM users u
         INNER JOIN countries c ON c.id = u.country_id
         INNER JOIN departments d ON d.id = u.department_id
         ORDER BY u.created_at DESC'
    );
    $users = $stmt->fetchAll();

    if ($users === []) {
        return [];
    }

    $userIds = array_column($users, 'id');
    $placeholders = implode(',', array_fill(0, count($userIds), '?'));
    $skillsStmt = $pdo->prepare(
        "SELECT us.user_id, s.name AS skill
         FROM user_skills us
         INNER JOIN skills s ON s.id = us.skill_id
         WHERE us.user_id IN ($placeholders)
         ORDER BY us.id ASC"
    );
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
    $password = (string) ($user['password'] ?? '');
    $passwordError = password_validation_error($password);
    if ($passwordError !== null) {
        throw new InvalidArgumentException($passwordError);
    }

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    if ($passwordHash === false) {
        throw new RuntimeException('Failed to secure password.');
    }

    $profilePicturePath = $user['profile_picture_path'] ?? null;

    try {
        $pdo->beginTransaction();

        $countryId = get_country_id($pdo, trim((string) ($user['country'] ?? '')));
        $departmentId = get_or_create_department_id($pdo, trim((string) ($user['department'] ?? '')));
        $skillIds = get_skill_ids($pdo, $skills);

        $stmt = $pdo->prepare(
            'INSERT INTO users (id, first_name, last_name, address, country_id, gender, username, department_id, password_hash, profile_picture_path)
             VALUES (:id, :first_name, :last_name, :address, :country_id, :gender, :username, :department_id, :password_hash, :profile_picture_path)'
        );
        $stmt->execute([
            'id' => $id,
            'first_name' => $user['first_name'] ?? '',
            'last_name' => $user['last_name'] ?? '',
            'address' => $user['address'] ?? '',
            'country_id' => $countryId,
            'gender' => $user['gender'] ?? '',
            'username' => $user['username'] ?? '',
            'department_id' => $departmentId,
            'password_hash' => $passwordHash,
            'profile_picture_path' => $profilePicturePath,
        ]);

        if ($skillIds !== []) {
            $skillsStmt = $pdo->prepare('INSERT INTO user_skills (user_id, skill_id) VALUES (:user_id, :skill_id)');
            foreach ($skillIds as $skillId) {
                $skillsStmt->execute([
                    'user_id' => $id,
                    'skill_id' => $skillId,
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
        'SELECT u.id,
                u.first_name,
                u.last_name,
                u.address,
                c.name AS country,
                u.gender,
                u.username,
                d.name AS department,
                u.profile_picture_path,
                u.created_at,
                u.updated_at
         FROM users u
         INNER JOIN countries c ON c.id = u.country_id
         INNER JOIN departments d ON d.id = u.department_id
         WHERE u.id = :id
         LIMIT 1'
    );
    $stmt->execute(['id' => $id]);
    $user = $stmt->fetch();

    if ($user === false) {
        return null;
    }

    $skillsStmt = $pdo->prepare(
        'SELECT s.name AS skill
         FROM user_skills us
         INNER JOIN skills s ON s.id = us.skill_id
         WHERE us.user_id = :user_id
         ORDER BY us.id ASC'
    );
    $skillsStmt->execute(['user_id' => $id]);
    $user['skills'] = array_column($skillsStmt->fetchAll(), 'skill');

    return $user;
}

function update_user(string $id, array $updated): bool
{
    $pdo = get_pdo();
    $skills = array_values(array_intersect(skill_options(), (array) ($updated['skills'] ?? [])));
    $shouldUpdatePicture = array_key_exists('profile_picture_path', $updated);

    try {
        $pdo->beginTransaction();

        $existsStmt = $pdo->prepare('SELECT id FROM users WHERE id = :id LIMIT 1');
        $existsStmt->execute(['id' => $id]);
        if ($existsStmt->fetch() === false) {
            $pdo->rollBack();
            return false;
        }

        $countryId = get_country_id($pdo, trim((string) ($updated['country'] ?? '')));
        $departmentId = get_or_create_department_id($pdo, trim((string) ($updated['department'] ?? '')));
        $newSkillIds = get_skill_ids($pdo, $skills);

        $sql = 'UPDATE users
                SET first_name = :first_name,
                    last_name = :last_name,
                    address = :address,
                    country_id = :country_id,
                    gender = :gender,
                    username = :username,
                    department_id = :department_id';
        if ($shouldUpdatePicture) {
            $sql .= ', profile_picture_path = :profile_picture_path';
        }
        $sql .= ', updated_at = NOW() WHERE id = :id';

        $params = [
            'first_name' => $updated['first_name'] ?? '',
            'last_name' => $updated['last_name'] ?? '',
            'address' => $updated['address'] ?? '',
            'country_id' => $countryId,
            'gender' => $updated['gender'] ?? '',
            'username' => $updated['username'] ?? '',
            'department_id' => $departmentId,
            'id' => $id,
        ];
        if ($shouldUpdatePicture) {
            $params['profile_picture_path'] = $updated['profile_picture_path'];
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        $existingStmt = $pdo->prepare('SELECT skill_id FROM user_skills WHERE user_id = :user_id');
        $existingStmt->execute(['user_id' => $id]);
        $existingSkillIds = array_map('intval', array_column($existingStmt->fetchAll(), 'skill_id'));

        $toDelete = array_values(array_diff($existingSkillIds, $newSkillIds));
        $toInsert = array_values(array_diff($newSkillIds, $existingSkillIds));

        if ($toDelete !== []) {
            $deletePlaceholders = implode(',', array_fill(0, count($toDelete), '?'));
            $deleteStmt = $pdo->prepare("DELETE FROM user_skills WHERE user_id = ? AND skill_id IN ($deletePlaceholders)");
            $deleteStmt->execute(array_merge([$id], $toDelete));
        }

        if ($toInsert !== []) {
            $insertSkillStmt = $pdo->prepare('INSERT INTO user_skills (user_id, skill_id) VALUES (:user_id, :skill_id)');
            foreach ($toInsert as $skillId) {
                $insertSkillStmt->execute([
                    'user_id' => $id,
                    'skill_id' => $skillId,
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
    $existing = find_user($id);

    $pdo = get_pdo();
    $stmt = $pdo->prepare('DELETE FROM users WHERE id = :id');
    $stmt->execute(['id' => $id]);

    $deleted = $stmt->rowCount() > 0;
    if ($deleted && $existing !== null) {
        delete_profile_picture((string) ($existing['profile_picture_path'] ?? ''));
    }

    return $deleted;
}
