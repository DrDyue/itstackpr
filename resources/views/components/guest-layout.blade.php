{{-- resources/views/components/guest-layout.blade.php --}}
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>IT Inventāra uzskaite</title>
    </head>
    <body style="margin: 0; padding: 0; background: #f5f5f7;">
        <main style="padding: 20px; max-width: 400px; margin: 40px auto; background: white; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.04);">
            {{ $slot }}
        </main>
    </body>
</html>
