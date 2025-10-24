<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Test' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h3 class="mb-0"><?= $title ?? 'Test' ?></h3>
                    </div>
                    <div class="card-body">
                        <p class="lead"><?= $message ?? 'Mensaje de prueba' ?></p>
                        <p><strong>Timestamp:</strong> <?= $timestamp ?? date('Y-m-d H:i:s') ?></p>
                        <p><strong>URL:</strong> <?= current_url() ?></p>
                        
                        <div class="mt-4">
                            <h5>Enlaces de prueba:</h5>
                            <ul class="list-group">
                                <li class="list-group-item">
                                    <a href="/test-simple" class="btn btn-outline-primary btn-sm">Test Simple</a>
                                </li>
                                <li class="list-group-item">
                                    <a href="/test-welcome" class="btn btn-outline-primary btn-sm">Test Welcome</a>
                                </li>
                                <li class="list-group-item">
                                    <a href="/test-mensajeria" class="btn btn-outline-primary btn-sm">Test Mensajería</a>
                                </li>
                                <li class="list-group-item">
                                    <a href="/health.php" class="btn btn-outline-success btn-sm">Health Check</a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
