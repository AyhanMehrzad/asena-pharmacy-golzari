<?php
$currentPage = 'autoship_plans';
require_once 'includes/admin_header.php';
require_once '../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    if (isset($_POST['action']) && $_POST['action'] === 'add_plan') {
        $name = trim($_POST['name']);
        $months = (int)$_POST['interval_months'];
        $discount = (int)$_POST['discount_percent'];
        $stmt = $pdo->prepare("INSERT INTO autoship_plans (name, interval_months, discount_percent) VALUES (?, ?, ?)");
        $stmt->execute([$name, $months, $discount]);
    } elseif (isset($_POST['action']) && $_POST['action'] === 'edit_plan') {
        $id = (int)$_POST['plan_id'];
        $name = trim($_POST['name']);
        $months = (int)$_POST['interval_months'];
        $discount = (int)$_POST['discount_percent'];
        $stmt = $pdo->prepare("UPDATE autoship_plans SET name = ?, interval_months = ?, discount_percent = ? WHERE id = ?");
        $stmt->execute([$name, $months, $discount, $id]);
    } elseif (isset($_POST['action']) && $_POST['action'] === 'delete_plan') {
        $id = (int)$_POST['plan_id'];
        $stmt = $pdo->prepare("DELETE FROM autoship_plans WHERE id = ?");
        $stmt->execute([$id]);
    }
    header('Location: autoship_plans.php');
    exit;
}

$stmt = $pdo->query("SELECT * FROM autoship_plans ORDER BY interval_months ASC");
$plans = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="p-8">
    <div class="flex justify-between items-center mb-8">
        <h1 class="text-2xl font-bold text-gray-800">مدیریت پلن‌های اشتراک هوشمند (Autoship)</h1>
        <button onclick="document.getElementById('addPlanModal').classList.remove('hidden')" class="bg-blue-600 text-white px-6 py-2 rounded-lg font-bold flex items-center gap-2 hover:bg-blue-700">
            <span class="material-symbols-outlined">add</span> افزودن پلن جدید
        </button>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <table class="w-full text-right text-sm">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="px-6 py-4">شناسه</th>
                    <th class="px-6 py-4">نام پلن</th>
                    <th class="px-6 py-4">بازه زمانی</th>
                    <th class="px-6 py-4">درصد تخفیف</th>
                    <th class="px-6 py-4 text-center">عملیات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php foreach($plans as $plan): ?>
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-6 py-4">#<?php echo $plan['id']; ?></td>
                    <td class="px-6 py-4 font-bold text-gray-800"><?php echo htmlspecialchars($plan['name']); ?></td>
                    <td class="px-6 py-4"><?php echo $plan['interval_months']; ?> ماه</td>
                    <td class="px-6 py-4 text-green-600 font-bold"><?php echo $plan['discount_percent']; ?>٪</td>
                    <td class="px-6 py-4 text-center space-x-2 space-x-reverse">
                        <button onclick="openEditModal(<?php echo $plan['id']; ?>, '<?php echo addslashes(htmlspecialchars($plan['name'])); ?>', <?php echo $plan['interval_months']; ?>, <?php echo $plan['discount_percent']; ?>)" class="text-blue-600 hover:text-blue-800"><span class="material-symbols-outlined">edit</span></button>
                        <form method="POST" class="inline" onsubmit="return confirm('آیا مطمئن هستید؟')">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="action" value="delete_plan">
                            <input type="hidden" name="plan_id" value="<?php echo $plan['id']; ?>">
                            <button type="submit" class="text-red-600 hover:text-red-800"><span class="material-symbols-outlined">delete</span></button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Modal -->
<div id="addPlanModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl p-6 w-full max-w-md shadow-xl relative">
        <button onclick="document.getElementById('addPlanModal').classList.add('hidden')" class="absolute top-4 left-4 text-gray-500 hover:text-red-500"><span class="material-symbols-outlined">close</span></button>
        <h2 class="text-xl font-bold text-gray-800 mb-6">افزودن پلن جدید</h2>
        <form method="POST" class="space-y-4">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="add_plan">
            <div>
                <label class="block text-sm font-bold mb-1">نام پلن</label>
                <input type="text" name="name" required class="w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-blue-500 outline-none">
            </div>
            <div>
                <label class="block text-sm font-bold mb-1">بازه زمانی (تعداد ماه)</label>
                <input type="number" name="interval_months" required min="1" class="w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-blue-500 outline-none">
            </div>
            <div>
                <label class="block text-sm font-bold mb-1">درصد تخفیف</label>
                <input type="number" name="discount_percent" required min="0" max="100" class="w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-blue-500 outline-none">
            </div>
            <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded-lg font-bold mt-4 hover:bg-blue-700">ذخیره</button>
        </form>
    </div>
</div>

<!-- Edit Modal -->
<div id="editPlanModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl p-6 w-full max-w-md shadow-xl relative">
        <button onclick="document.getElementById('editPlanModal').classList.add('hidden')" class="absolute top-4 left-4 text-gray-500 hover:text-red-500"><span class="material-symbols-outlined">close</span></button>
        <h2 class="text-xl font-bold text-gray-800 mb-6">ویرایش پلن</h2>
        <form method="POST" class="space-y-4">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="edit_plan">
            <input type="hidden" name="plan_id" id="edit_plan_id" value="">
            <div>
                <label class="block text-sm font-bold mb-1">نام پلن</label>
                <input type="text" name="name" id="edit_name" required class="w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-blue-500 outline-none">
            </div>
            <div>
                <label class="block text-sm font-bold mb-1">بازه زمانی (تعداد ماه)</label>
                <input type="number" name="interval_months" id="edit_months" required min="1" class="w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-blue-500 outline-none">
            </div>
            <div>
                <label class="block text-sm font-bold mb-1">درصد تخفیف</label>
                <input type="number" name="discount_percent" id="edit_discount" required min="0" max="100" class="w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-blue-500 outline-none">
            </div>
            <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded-lg font-bold mt-4 hover:bg-blue-700">ذخیره تغییرات</button>
        </form>
    </div>
</div>

<script>
function openEditModal(id, name, months, discount) {
    document.getElementById('edit_plan_id').value = id;
    document.getElementById('edit_name').value = name;
    document.getElementById('edit_months').value = months;
    document.getElementById('edit_discount').value = discount;
    document.getElementById('editPlanModal').classList.remove('hidden');
}
</script>
<?php include 'includes/footer.php'; ?>
