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
                $stmt = $db->prepare("INSERT INTO members (survey_id, name, gender, birthday, occupation) VALUES (:survey_id, :name, :gender, :birthday, :occupation)");
                $stmt->execute([
                    ':survey_id' => $survey_id,
                    ':name' => $member['name'],
                    ':gender' => $member['gender'],
                    ':birthday' => $member['birthday'],
                    ':occupation' => $member['occupation']
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
    <!-- <script src="https://cdn.tailwindcss.com"></script> -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" referrerpolicy="no-referrer" />
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-2xl mx-auto">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold">Add New Survey</h1>
                <a href="index.php" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Back to List</a>
            </div>

            <form method="POST" class="bg-white rounded-lg shadow p-6">
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="division">Division</label>
                    <!-- <input type="text" name="division" id="division" required
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
                           class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"> -->
                    <select name="district" id="district" required
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight bg-white focus:outline-none focus:shadow-outline">
                        <option value="">Select District</option>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="address">Address</label>
                    <textarea name="address" id="address" required
                          class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"></textarea>
                </div>

                <div id="members-container">
                    <h3 class="text-lg font-semibold mb-4">Family Members</h3>
                    <div class="member-entry mb-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2">Name</label>
                                <input type="text" name="members[0][name]"
                                       class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2">Gender</label>
                                <select name="members[0][gender]" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight bg-white focus:outline-none focus:shadow-outline">
                                    <option value="">Select Gender</option>
                                    <option value="m">Male</option>
                                    <option value="f">Female</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2">Birthday</label>
                                <input type="date" name="members[0][birthday]"
                                       class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2">Occupation</label>
                                <select name="members[0][occupation]" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight bg-white focus:outline-none focus:shadow-outline">
                                    <option value="">Select Occupation</option>
                                    <option value="employed">Employed</option>
                                    <option value="unemployed">Unemployed</option>
                                    <option value="student">Student</option>
                                    <option value="retired">Retired</option>
                                    <option value="homemaker">Homemaker</option>
                                </select>
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
        function populate_divisions(selector){
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
                select.add(option);
            }
        }
        populate_divisions('division');
        function update_districts(){
            // Get the selected division
            // var division = document.getElementById('division').value;
            var division = event.target.value;
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
                district_select.options[district_select.options.length] = new Option(districts[index], districts[index]);
            }
        }
    </script>
</body>
</html>
