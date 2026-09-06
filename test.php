<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$subject = \App\Models\BpsSubject::first();
if (!$subject) {
    echo "No subjects found\n";
    exit;
}
echo "First subject domain: {$subject->domain_id}, subcat: {$subject->subcat_id}\n";
$subject->load('category');
echo "Category title (lazy): " . ($subject->category ? $subject->category->title : 'null') . "\n";

$subjects = \App\Models\BpsSubject::with(['category' => function($q) use ($subject) {
    $q->where('domain_id', $subject->domain_id);
}])->where('domain_id', $subject->domain_id)->limit(5)->get();

echo "Count subjects eager loaded: " . $subjects->count() . "\n";
foreach($subjects as $s) {
    echo "Subject: {$s->sub_id}, Category: " . ($s->category ? $s->category->title : 'null') . "\n";
}
