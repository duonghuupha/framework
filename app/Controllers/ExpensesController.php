<?php
class ExpensesController extends Controller{
	protected $expenseModel;
	public function __construct(){
		$this->expenseModel = new Expenses();
	}

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
                'supplie' => $input['search']['supplier'] ?? '',
                'date_start' => $date_from,
                'date_end' => $date_to
            ],
            'filters' => [],
            'order' => ['id' => 'DESC']
        ];
        $result = $this->expenseModel->listExpenses($params);
        return $this->json($result);
	}

	/**
	 * Them mo phieu chi
	 */
	public function add(){
		try{
			$this->checkToken();
			$input = Input::all();
			$expense = $this->expenseModel->createExpense($input);
			return $this->json(['id' => $expense]);
		}catch(Exception $e){
			throw $e;
		}
	}

	/**
	 * Huy phieu chi
	 */
	public function cancelExpense($id){
		try{
			$payload = $this->checkToken();
			$input = Input::all();
			$reason = trim($input['data'] ?? '');
			if($reason === ''){
				throw new Exception("Vui lòng nhập lý do hủy phiếu");
			}
			$result = $this->expenseModel->cancelExpense($id, $reason, $payload['user_id']);
			return $this->json(['result' => $result]);
		}catch(Exception $e){	
			return $this->json([], 'error', $e->getMessage());
		}
	}
}
?>