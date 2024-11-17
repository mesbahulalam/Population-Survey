<?php
// index.php
session_start();
require_once 'config.php';

// Stats Queries
$stats = [
    'total_surveys' => $db->query("SELECT COUNT(*) FROM surveys")->fetchColumn(),
    'total_members' => $db->query("SELECT COUNT(*) FROM members")->fetchColumn(),
    'avg_members_per_survey' => $db->query("SELECT ROUND(AVG(member_count), 1) FROM 
        (SELECT COUNT(*) as member_count FROM members GROUP BY survey_id) as counts")->fetchColumn(),
    'divisions_count' => $db->query("SELECT COUNT(DISTINCT division) FROM surveys")->fetchColumn(),
    'districts_count' => $db->query("SELECT COUNT(DISTINCT district) FROM surveys")->fetchColumn()
];

// Get unique divisions and districts for select2
$divisions = $db->query("SELECT DISTINCT division FROM surveys ORDER BY division")->fetchAll(PDO::FETCH_COLUMN);
$districts = $db->query("SELECT DISTINCT district FROM surveys ORDER BY district")->fetchAll(PDO::FETCH_COLUMN);

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$stmt = $db->query("SELECT COUNT(*) FROM surveys");
$total_records = $stmt->fetchColumn();
$total_pages = ceil($total_records / $limit);

$stmt = $db->prepare("SELECT s.*, GROUP_CONCAT(CONCAT(m.name, '|', m.birthday) SEPARATOR ',') as members 
                      FROM surveys s 
                      LEFT JOIN members m ON s.id = m.survey_id 
                      GROUP BY s.id 
                      LIMIT :limit OFFSET :offset");
$stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$surveys = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Population Survey</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <!-- Stats Section -->
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-8">
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-600">Total Surveys</h3>
                <p class="text-3xl font-bold text-blue-600"><?= number_format($stats['total_surveys']) ?></p>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-600">Total Members</h3>
                <p class="text-3xl font-bold text-green-600"><?= number_format($stats['total_members']) ?></p>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-600">Avg Members/Survey</h3>
                <p class="text-3xl font-bold text-purple-600"><?= $stats['avg_members_per_survey'] ?></p>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-600">Total Divisions</h3>
                <p class="text-3xl font-bold text-orange-600"><?= number_format($stats['divisions_count']) ?></p>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-600">Total Districts</h3>
                <p class="text-3xl font-bold text-red-600"><?= number_format($stats['districts_count']) ?></p>
            </div>
        </div>

        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold">Population Survey</h1>
            <a href="add.php" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Add New Survey</a>
        </div>

        <div class="bg-white rounded-lg shadow overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="bg-gray-50">
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Division</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">District</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Address</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Members</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($surveys as $survey): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($survey['division']) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($survey['district']) ?></td>
                        <td class="px-6 py-4"><?= htmlspecialchars($survey['address']) ?></td>
                        <td class="px-6 py-4">
                            <?php
                            if ($survey['members']) {
                                $formatted_members = [];
                                $members = explode(',', $survey['members']);
                                foreach ($members as $member) {
                                    list($name, $birthday) = explode('|', $member);
                                    $formatted_members[] = htmlspecialchars("$name (" . date('Y-m-d', strtotime($birthday)) . ")");
                                }
                                echo implode(', ', $formatted_members);
                            }
                            ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <a href="edit.php?id=<?= $survey['id'] ?>" class="text-indigo-600 hover:text-indigo-900">Edit</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="flex justify-center mt-6">
            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                <?php
                $show_dots_start = false;
                $show_dots_end = false;
                
                for ($i = 1; $i <= $total_pages; $i++) {
                    if ($i == 1 || $i == $total_pages || ($i >= $page - 2 && $i <= $page + 2)) {
                        $active = $i == $page ? 'bg-blue-50 border-blue-500 text-blue-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50';
                        echo "<a href='?page=$i' class='relative inline-flex items-center px-4 py-2 border text-sm font-medium $active'>$i</a>";
                    } elseif ($i < $page - 2 && !$show_dots_start) {
                        echo "<span class='relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-gray-700'>...</span>";
                        $show_dots_start = true;
                    } elseif ($i > $page + 2 && !$show_dots_end) {
                        echo "<span class='relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-gray-700'>...</span>";
                        $show_dots_end = true;
                    }
                }
                ?>
            </nav>
        </div>
    </div>
</body>
</html>
