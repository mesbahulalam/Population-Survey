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
                $stmt = $db->prepare("INSERT INTO members (survey_id, name, gender, birthday, occupation) VALUES (:survey_id, :name, :gender, :birthday, :occupation)");
                $stmt->execute([
                    ':survey_id' => $id,
                    ':name' => $member['name'],
                    ':gender' => $member['gender'],
                    ':birthday' => $member['birthday'],
                    ':occupation' => $member['occupation']
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
$stmt = $db->prepare("SELECT s.*, GROUP_CONCAT(CONCAT(m.name, '|', m.gender, '|', m.birthday, '|', m.occupation) SEPARATOR ',') as members 
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
        list($name, $gender, $birthday, $occupation) = explode('|', $member);
        $members[] = ['name' => $name, 'gender' => $gender, 'birthday' => $birthday, 'occupation' => $occupation];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Survey</title>
    <!-- <script src="https://cdn.tailwindcss.com"></script> -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" referrerpolicy="no-referrer" />
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-2xl mx-auto">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold">Edit Survey</h1>
                <a href="index.php" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Back to List</a>
            </div>

            <form method="POST" class="bg-white rounded-lg shadow p-6">
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="division">Division</label>
                    <!-- <input type="text" name="division" id="division" required
                           value="<?= htmlspecialchars($survey['division']) ?>"
                           class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"> -->
                    <select name="division" id="division" required
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight bg-white focus:outline-none focus:shadow-outline"
                            onchange="update_districts()">
                        <option value="">Select Division</option>
                    </select>

                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="district">District</label>
                    <!-- <input type="text" name="district" id="district" required
                           value="<?= htmlspecialchars($survey['district']) ?>"
                           class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"> -->
                    <select name="district" id="district" required
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight bg-white focus:outline-none focus:shadow-outline">
                        <option value="">Select District</option>
                    </select>
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
                        <?php if($index != 0) echo '<hr class="my-4 border-t border-gray-300">'; ?>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2">Name</label>
                                <input type="text" name="members[<?= $index ?>][name]"
                                       value="<?= htmlspecialchars($member['name']) ?>"
                                       class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2">Gender</label>
                                <select name="members[<?= $index ?>][gender]" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight bg-white focus:outline-none focus:shadow-outline">
                                    <option value="">Select Gender</option>
                                    <option value="m" <?php if($member['gender'] == 'm') echo ' selected'; ?>>Male</option>
                                    <option value="f" <?php if($member['gender'] == 'f') echo ' selected'; ?>>Female</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2">Birthday</label>
                                <input type="date" name="members[<?= $index ?>][birthday]"
                                       value="<?= htmlspecialchars($member['birthday']) ?>"
                                       class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2">Occupation</label>
                                <select name="members[<?= $index ?>][occupation]" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight bg-white focus:outline-none focus:shadow-outline">
                                    <option value="">Select Occupation</option>
                                    <option value="employed" <?php if($member['occupation'] == 'employed') echo ' selected'; ?>>Employed</option>
                                    <option value="unemployed" <?php if($member['occupation'] == 'unemployed') echo ' selected'; ?>>Unemployed</option>
                                    <option value="student" <?php if($member['occupation'] == 'student') echo ' selected'; ?>>Student</option>
                                    <option value="retired" <?php if($member['occupation'] == 'retired') echo ' selected'; ?>>Retired</option>
                                    <option value="homemaker" <?php if($member['occupation'] == 'homemaker') echo ' selected'; ?>>Homemaker</option>
                                </select>
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
                <hr class="my-4 border-t border-gray-300">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Name</label>
                        <input type="text" name="members[${memberCount}][name]"
                                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Gender</label>
                        <select name="members[${memberCount}][gender]" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight bg-white focus:outline-none focus:shadow-outline">
                            <option value="">Select Gender</option>
                            <option value="m">Male</option>
                            <option value="f">Female</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Birthday</label>
                        <input type="date" name="members[${memberCount}][birthday]"
                                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Occupation</label>
                        <select name="members[${memberCount}][occupation]" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight bg-white focus:outline-none focus:shadow-outline">
                            <option value="">Select Occupation</option>
                            <option value="employed">Employed</option>
                            <option value="unemployed">Unemployed</option>
                            <option value="student">Student</option>
                            <option value="retired">Retired</option>
                            <option value="homemaker">Homemaker</option>
                        </select>
                    </div>
                </div>
            `;
            container.appendChild(newMember);
            memberCount++;
        }
        function populate_divisions(selector, division){
            var divisions = [
                "Barisal",
                "Chittagong",
                "Dhaka",
                "Khulna",
                "Mymensingh",
                "Rajshahi",
                "Rangpur",
                "Sylhet"
            ];
            var select = document.getElementById(selector);
            for(index in divisions) {
                var option = document.createElement("option");
                option.text = divisions[index];
                option.value = divisions[index];
                if (division && division === divisions[index]) {
                    option.selected = true;
                }
                select.add(option);
            }
        }
        // populate_divisions('division', '<?= $survey['division'] ?>');
        function update_districts(district){
            // Get the selected division
            var division = document.getElementById('division').value;
            // var division = event.target.value;
            // Get the districts select element
            var district_select = document.getElementById('district');
            // Remove all existing options
            district_select.options.length = 0;
            // Get the districts for the selected division
            var districts = [];
            switch(division) {
                case 'Barisal':
                    districts = ["Barguna", "Barisal", "Bhola", "Jhalokati", "Patuakhali", "Pirojpur"];
                    break;
                case 'Chittagong':
                    districts = ["Bandarban", "Brahmanbaria", "Chandpur", "Chittagong", "Comilla", "Cox's Bazar", "Feni", "Khagrachhari", "Lakshmipur", "Noakhali", "Rangamati"];
                    break;
                case 'Dhaka':
                    districts = ["Dhaka", "Faridpur", "Gazipur", "Gopalganj", "Kishoreganj", "Madaripur", "Manikganj", "Munshiganj", "Narayanganj", "Narsingdi", "Rajbari", "Shariatpur", "Tangail"];
                    break;
                case 'Rajshahi':
                    districts = ["Bogra", "Joypurhat", "Naogaon", "Natore", "Chapainawabganj", "Pabna", "Rajshahi", "Sirajganj"];
                    break;
                case 'Rangpur':
                    districts = ["Dinajpur", "Gaibandha", "Kurigram", "Lalmonirhat", "Nilphamari", "Panchagarh", "Rangpur", "Thakurgaon"];
                    break;
                case 'Sylhet':
                    districts = ["Habiganj", "Maulvibazar", "Sunamganj", "Sylhet"];
                    break;
                case 'Khulna':
                    districts = ["Bagerhat", "Chuadanga", "Jessore", "Jhenaidah", "Khulna", "Kushtia", "Magura", "Meherpur", "Narail", "Satkhira"];
                    break;
                case 'Mymensingh':
                    districts = ["Jamalpur", "Mymensingh", "Netrokona", "Sherpur"];
                    break;
                }
            for(index in districts) {
                var option = document.createElement("option");
                option.text = districts[index];
                option.value = districts[index];
                if (district && district === districts[index]) {
                    option.selected = true;
                }
                district_select.add(option);
            }
        }
        function populate_districts(selector, division, district){
            var division_select = document.getElementById('division');
            division_select.addEventListener('change', update_districts);
            populate_divisions('division', division);
            update_districts(district);
        }
        populate_districts('district', '<?= $survey['division'] ?>', '<?= $survey['district'] ?>');
    </script>
</body>
</html>
