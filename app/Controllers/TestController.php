<?php

namespace App\Controllers;

use CodeIgniter\Controller;

class TestController extends Controller
{
    public function index()
    {
        return $this->response->setJSON([
            'success' => true,
            'message' => 'TestController funcionando correctamente',
            'timestamp' => date('Y-m-d H:i:s'),
            'url' => current_url(),
            'method' => $this->request->getMethod()
        ]);
    }
    
    public function welcome()
    {
        $data = [
            'title' => 'Test Welcome - Sin Autenticación',
            'message' => 'Esta es una página de prueba sin autenticación',
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        return view('test/welcome', $data);
    }
    
    public function simple()
    {
        echo "<h1>Test Simple</h1>";
        echo "<p>Esta es una página de prueba simple</p>";
        echo "<p>Timestamp: " . date('Y-m-d H:i:s') . "</p>";
        echo "<p>URL: " . current_url() . "</p>";
    }
}
