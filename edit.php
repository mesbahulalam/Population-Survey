<?php
// edit.php
session_start();
require_once 'config.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db->beginTransaction();
        
        $stmt = $db->prepare("UPDATE surveys SET division = :division, district = :district, address = :address WHERE id = :id");
        $stmt->execute([
            ':division' => $_POST['division'],
            ':district' => $_POST['district'],
            ':address' => $_POST['address'],
            ':id' => $id
        ]);
        
        // Delete existing members
        $stmt = $db->prepare("DELETE FROM members WHERE survey_id = :survey_id");
        $stmt->execute([':survey_id' => $id]);
        
        // Insert updated members
        foreach ($_POST['members'] as $member) {
            if (!empty($member['name']) && !empty($member['birthday'])) {
                $stmt = $db->prepare("INSERT INTO members (survey_id, name, birthday) VALUES (:survey_id, :name, :birthday)");
                $stmt->execute([
                    ':survey_id' => $id,
                    ':name' => $member['name'],
                    ':birthday' => $member['birthday']
                ]);
            }
        }
        
        $db->commit();
        $_SESSION['success'] = 'Survey updated successfully';
        header('Location: index.php');
        exit;
    } catch (Exception $e) {
        $db->rollBack();
        $_SESSION['error'] = 'Error updating survey: ' . $e->getMessage();
    }
}

// Fetch existing survey data
$stmt = $db->prepare("SELECT s.*, GROUP_CONCAT(CONCAT(m.name, '|', m.birthday) SEPARATOR ',') as members 
                      FROM surveys s 
                      LEFT JOIN members m ON s.id = m.survey_id 
                      WHERE s.id = :id 
                      GROUP BY s.id");
$stmt->execute([':id' => $id]);
$survey = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$survey) {
    $_SESSION['error'] = 'Survey not found';
    header('Location: index.php');
    exit;
}

$members = [];
if ($survey['members']) {
    foreach (explode(',', $survey['members']) as $member) {
        list($name, $birthday) = explode('|', $member);
        $members[] = ['name' => $name, 'birthday' => $birthday];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Survey</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-2xl mx-auto">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold">Edit Survey</h1>
                <a href="index.php" class="text-blue-500 hover:text-blue-600">Back to List</a>
            </div>

            <form method="POST" class="bg-white rounded-lg shadow p-6">
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="division">Division</label>
                    <input type="text" name="division" id="division" required
                           value="<?= htmlspecialchars($survey['division']) ?>"
                           class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="district">District</label>
                    <input type="text" name="district" id="district" required
                           value="<?= htmlspecialchars($survey['district']) ?>"
                           class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="address">Address</label>
                    <textarea name="address" id="address" required
                          class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"><?= htmlspecialchars($survey['address']) ?></textarea>
                </div>

                <div id="members-container">
                    <h3 class="text-lg font-semibold mb-4">Family Members</h3>
                    <?php foreach ($members as $index => $member): ?>
                    <div class="member-entry mb-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2">Name</label>
                                <input type="text" name="members[<?= $index ?>][name]"
                                       value="<?= htmlspecialchars($member['name']) ?>"
                                       class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2">Birthday</label>
                                <input type="date" name="members[<?= $index ?>][birthday]"
                                       value="<?= htmlspecialchars($member['birthday']) ?>"
                                       class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <button type="button" onclick="addMember()"
                        class="mb-4 bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">
                    Add Member
                </button>

                <div class="flex items-center justify-end">
                    <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                        Update Survey
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let memberCount = <?= count($members) ?>;
        
        function addMember() {
            const container = document.getElementById('members-container');
            const newMember = document.createElement('div');
            newMember.className = 'member-entry mb-4';
            newMember.innerHTML = `
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Name</label>
                        <input type="text" name="members[${memberCount}][name]"
                               class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Birthday</label>
                        <input type="date" name="members[${memberCount}][birthday]"
                               class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    </div>
                </div>
            `;
            container.appendChild(newMember);
            memberCount++;
        }
    </script>
</body>
</html>
