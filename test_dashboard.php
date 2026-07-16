<?php
$u = \App\Models\User::first();
auth()->login($u);
try {
    $res = app()->make(Illuminate\Contracts\Http\Kernel::class)->handle(Illuminate\Http\Request::create('/dashboard', 'GET'));
    echo "STATUS: " . $res->getStatusCode() . "\n";
    if ($res->getStatusCode() == 500) {
        echo $res->getContent();
    }
} catch (\Exception $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n" . $e->getTraceAsString();
}
