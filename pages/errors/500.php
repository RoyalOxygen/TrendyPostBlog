<?php
if (!defined('TRENDYPOST')) die('Direct access not permitted.');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 - Internal Server Error</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8 text-center">
                <div class="display-1 text-muted mb-4" style="font-size: 100px;">500</div>
                <h2 class="mb-3">Internal Server Error</h2>
                <p class="text-muted">Something went wrong on our end. Please try again later.</p>
                <a href="<?php echo url(); ?>" class="btn btn-success">Go to Homepage</a>
            </div>
        </div>
    </div>
</body>
</html>