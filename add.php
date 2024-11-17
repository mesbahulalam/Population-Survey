<?php
// add.php
session_start();
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db->beginTransaction();
        
        $stmt = $db->prepare("INSERT INTO surveys (division, district, address) VALUES (:division, :district, :address)");
        $stmt->execute([
            ':division' => $_POST['division'],
            ':district' => $_POST['district'],
            ':address' => $_POST['address']
        ]);
        
        $survey_id = $db->lastInsertId();
        
        foreach ($_POST['members'] as $member) {
            if (!empty($member['name']) && !empty($member['birthday'])) {
                $stmt = $db->prepare("INSERT INTO members (survey_id, name, birthday) VALUES (:survey_id, :name, :birthday)");
                $stmt->execute([
                    ':survey_id' => $survey_id,
                    ':name' => $member['name'],
                    ':birthday' => $member['birthday']
                ]);
            }
        }
        
        $db->commit();
        $_SESSION['success'] = 'Survey added successfully';
        header('Location: index.php');
        exit;
    } catch (Exception $e) {
        $db->rollBack();
        $_SESSION['error'] = 'Error adding survey: ' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Survey</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-2xl mx-auto">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold">Add New Survey</h1>
                <a href="index.php" class="text-blue-500 hover:text-blue-600">Back to List</a>
            </div>

            <form method="POST" class="bg-white rounded-lg shadow p-6">
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="division">Division</label>
                    <input type="text" name="division" id="division" required
                           class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="district">District</label>
                    <input type="text" name="district" id="district" required
                           class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="address">Address</label>
                    <textarea name="address" id="address" required
                          class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"></textarea>
                </div>

                <div id="members-container">
                    <h3 class="text-lg font-semibold mb-4">Members</h3>
                    <div class="member-entry mb-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2">Name</label>
                                <input type="text" name="members[0][name]"
                                       class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2">Birthday</label>
                                <input type="date" name="members[0][birthday]"
                                       class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                            </div>
                        </div>
                    </div>
                </div>

                <button type="button" onclick="addMember()"
                        class="mb-4 bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">
                    Add Member
                </button>

                <div class="flex items-center justify-end">
                    <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                        Save Survey
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let memberCount = 1;
        
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
