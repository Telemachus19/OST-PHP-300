<?php

function countries(): array
{
    static $options = null;

    if (is_array($options)) {
        return $options;
    }

    $pdo = get_pdo();
    $stmt = $pdo->query('SELECT name FROM countries ORDER BY name ASC');
    $rows = array_column($stmt->fetchAll(), 'name');
    $options = array_merge(['Select Country'], $rows);

    return $options;
}

function skill_options(): array
{
    static $options = null;

    if (is_array($options)) {
        return $options;
    }

    $pdo = get_pdo();
    $stmt = $pdo->query('SELECT name FROM skills ORDER BY name ASC');
    $options = array_column($stmt->fetchAll(), 'name');

    return $options;
}

function get_country_id(PDO $pdo, string $country): int
{
    $stmt = $pdo->prepare('SELECT id FROM countries WHERE name = :name LIMIT 1');
    $stmt->execute(['name' => $country]);
    $id = $stmt->fetchColumn();

    if ($id === false) {
        throw new InvalidArgumentException('Invalid country selected.');
    }

    return (int) $id;
}

function get_or_create_department_id(PDO $pdo, string $department): int
{
    if ($department === '') {
        throw new InvalidArgumentException('Department is required.');
    }

    $selectStmt = $pdo->prepare('SELECT id FROM departments WHERE name = :name LIMIT 1');
    $selectStmt->execute(['name' => $department]);
    $existingId = $selectStmt->fetchColumn();

    if ($existingId !== false) {
        return (int) $existingId;
    }

    try {
        $insertStmt = $pdo->prepare('INSERT INTO departments (name) VALUES (:name)');
        $insertStmt->execute(['name' => $department]);

        return (int) $pdo->lastInsertId();
    } catch (PDOException $e) {
        if ($e->getCode() !== '23000') {
            throw $e;
        }

        $retryStmt = $pdo->prepare('SELECT id FROM departments WHERE name = :name LIMIT 1');
        $retryStmt->execute(['name' => $department]);
        $retryId = $retryStmt->fetchColumn();

        if ($retryId === false) {
            throw new RuntimeException('Department resolution failed.');
        }

        return (int) $retryId;
    }
}

function get_skill_ids(PDO $pdo, array $skills): array
{
    if ($skills === []) {
        return [];
    }

    $skills = array_values(array_unique($skills));
    $placeholders = implode(',', array_fill(0, count($skills), '?'));
    $stmt = $pdo->prepare("SELECT id, name FROM skills WHERE name IN ($placeholders)");
    $stmt->execute($skills);

    $idsByName = [];
    foreach ($stmt->fetchAll() as $row) {
        $idsByName[$row['name']] = (int) $row['id'];
    }

    $skillIds = [];
    foreach ($skills as $skill) {
        if (!array_key_exists($skill, $idsByName)) {
            throw new InvalidArgumentException('Invalid skill selected.');
        }

        $skillIds[] = $idsByName[$skill];
    }

    return $skillIds;
}
