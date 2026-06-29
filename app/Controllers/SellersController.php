<?php
class SellersController extends Controller{
    protected Sellers $sellerModel;
    public function __construct(){
        $this->sellerModel = new Sellers();
    }

    /**
     * Danh sách hóa đơn bán
     */
    public function index(){
        try {
            $this->checkToken();
            $params = Input::all();
            $result = $this->sellerModel->listSellers($params);
            return $this->json($result);
        } catch (Exception $e) {
            return $this->json([], 'error', $e->getMessage());
        }
    }

    /**
     * Chi tiết hóa đơn
     */
    public function detail(){
        try {
            $this->checkToken();
            $input = Input::all();
            if (empty($input['id'])) {
                throw new Exception("Thiếu ID hóa đơn.");
            }
            $header = $this->sellerModel->find((int)$input['id']);
            if (!$header) {
                throw new Exception("Không tìm thấy hóa đơn.");
            }
            $products = $this->sellerModel->detailSeller((int)$input['id']);
            return $this->json(['header' => $header,'products' => $products]);
        } catch (Exception $e) {
            return $this->json([], 'error', $e->getMessage());
        }
    }

    /**
     * Thêm hóa đơn bán
     */
    public function add(){
        try {
            $this->checkToken();
            $input = Input::all();
            $sellerId = $this->sellerModel->createSeller($input);
            return $this->json(['id' => $sellerId]);
        } catch (Exception $e) {
            return $this->json([], 'error', $e->getMessage());
        }
    }
}