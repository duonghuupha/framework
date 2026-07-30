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
        /*try {
            $this->checkToken();
            $params = Input::all();
            $result = $this->sellerModel->listSellers($params);
            return $this->json($result);
        } catch (Exception $e) {
            return $this->json([], 'error', $e->getMessage());
        }*/
        $payload = $this->checkToken();
        $input = Input::all();
        $date_start = $input['search']['date_start'] ?? '';
        $date_end = $input['search']['date_end'] ?? '';

        $date_from = '';
        if(!empty($date_start)){
            $objectDate = DateTime::createFromFormat('d/m/Y', $date_start);
            if($objectDate){
                $date_from = $objectDate->format('Y-m-d');
            }
        }

        $date_to = '';
        if(!empty($date_end)){
            $objectDate = DateTime::createFromFormat('d/m/Y', $date_end);
            if($objectDate){
                $date_to = $objectDate->format('Y-m-d');
            }
        }

        $params = [
            'page' => $input['page'] ?? 1,
            'limit' => $input['limit'] ?? 20,
            'search' => [
                'code' => $input['search']['code'] ?? '',
                'customer' => $input['search']['customer'] ?? '',
                'product' => $input['search']['product'] ?? '',
                'date_start' => $date_from,
                'date_end' => $date_to
            ],
            'filters' => [],
            'order' => ['id' => 'DESC']
        ];
        $result = $this->sellerModel->listSellers($params);
        return $this->json($result);
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