<?php
class ReceiptsController extends Controller{
	protected $receiptModel;
	public function __construct(){
		$this->receiptModel = new Receipts();
	}

	/**
	 * Danh sách phieu thu
	 */
	public function index(){
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
                'date_start' => $date_from,
                'date_end' => $date_to
            ],
            'filters' => [],
            'order' => ['id' => 'DESC']
        ];
        $result = $this->receiptModel->listReceipts($params);
        return $this->json($result);
	}

	/**
	 * Thêm mới phieu thu
	 */
	public function add(){
        try {
            $this->checkToken();
            $input = Input::all();
            $receiptId = $this->receiptModel->createReceipt($input);
            return $this->json(['id' => $receiptId]);
        } catch (Exception $e) {
            return $this->json([],'error', $e->getMessage());
        }
    }

    /**
     * Cập nhật phiếu thu
     */
    public function update(){
        try {
            $this->checkToken();
            $input = Input::all();
            $result = $this->receiptModel->updateReceipt((int) $input['id'], $input);
            return $this->json(['result' => $result]);
        } catch (Exception $e) {
            return $this->json([],'error', $e->getMessage());
        }
    }
}
?>