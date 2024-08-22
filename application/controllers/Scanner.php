<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Scanner extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->library('session');
    }

    public function index() {
        $this->load->helper('url');
        $this->load->view('scanner_view');
    }

    public function scan() {
        $this->load->model('Scanner_model');
        $result = $this->Scanner_model->scan_directory('.');
        $data['result'] = $result;
        $this->load->view('scanner_result', $data);
    }

    public function upload() {
        $config['upload_path'] = './uploads/';
        $config['allowed_types'] = 'gif|jpg|png|php|html|txt|zip';
        $config['max_size'] = 1024000; // 100MB

        $this->load->library('upload', $config);

        if (!$this->upload->do_upload('userfile')) {
            $error = array('error' => $this->upload->display_errors());
            $this->load->view('scanner_view', $error);
        } else {
            $data = $this->upload->data();
            $file_path = $data['full_path'];

            if ($data['file_ext'] === '.zip') {
                $extract_path = './uploads/' . $data['raw_name'];
                mkdir($extract_path, 0755, true);

                $this->load->model('Scanner_model');
                if ($this->Scanner_model->extract_zip($file_path, $extract_path)) {
                    $result = $this->Scanner_model->scan_directory($extract_path);
                    $data['result'] = $result;
                } else {
                    $data['error'] = 'Failed to extract the zip file.';
                }

                // Hapus folder yang diekstrak setelah scan selesai
                $this->load->helper('file');
                delete_files($extract_path, TRUE);
                $this->delete_directory($extract_path);
            } else {
                // Scan the uploaded file
                $this->load->model('Scanner_model');
                $content = $this->Scanner_model->read_file($file_path);
                $check = $this->Scanner_model->check_backdoor($content);
                $result = [
                    'file' => $file_path,
                    'status' => empty($check) ? 'Safe' : 'Found (' . $check . ')',
                    'color' => empty($check) ? 'green' : 'red'
                ];

                $data['result'] = [$result];
            }

            // Hapus file yang di-upload setelah scan selesai
            unlink($file_path);

            // Tambahkan logika untuk mengumpulkan backdoor yang ditemukan
            $backdoors = [];
            foreach ($data['result'] as $res) {
                if (strpos($res['status'], 'Found') !== false) {
                    $backdoors[] = $res['file'] . ' => ' . $res['status'];
                }
            }
            $data['backdoors'] = $backdoors;

            // Simpan backdoors ke sesi
            $this->session->set_userdata('backdoors', $backdoors);

            $this->load->view('scanner_result', $data);
        }
    }

    public function export_backdoors() {
        // Ambil backdoors dari sesi
        $backdoors = $this->session->userdata('backdoors');
        $content = implode("\n", $backdoors);
        $this->load->helper('download');
        force_download('backdoors.txt', $content);
    }

    private function delete_directory($dir) {
        if (!is_dir($dir)) {
            return;
        }
        $files = array_diff(scandir($dir), array('.', '..'));
        foreach ($files as $file) {
            (is_dir("$dir/$file")) ? $this->delete_directory("$dir/$file") : unlink("$dir/$file");
        }
        rmdir($dir);
    }
}
?>