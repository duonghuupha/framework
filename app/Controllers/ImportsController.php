<?php

class ImportsController extends Controller
{
    protected $importsModel;

    public function __construct()
    {
        $this->importsModel = new Imports();
    }

    /**
     * Danh sách phiếu nhập
     */
    function index()
    {
        $payload = $this->checkToken();

        $input = Input::all();

        $created_at = $input['search']['created_at'] ?? '';

        $date = '';

        if (!empty($created_at)) {

            $objectDate = DateTime::createFromFormat(
                'd/m/Y',
                $created_at
            );

            if ($objectDate) {

                $date = $objectDate->format('Y-m-d');

            }

        }

        $params = [

            'page' => $input['page'] ?? 1,

            'limit' => $input['limit'] ?? 20,

            'search' => [

                'supplier_id' => $input['search']['supplier_id'] ?? '',

                'created_at' => $date,

                'product' => $input['search']['product'] ?? ''

            ],

            'filters' => [],

            'order' => [

                'id' => 'DESC'

            ]

        ];

        $result = $this->importsModel->listImports($params);

        return $this->json($result);
    }

    /**
     * Thêm phiếu nhập
     */
    function add()
    {
        $payload = $this->checkToken();

        $input = Input::all();

        try {

            $importId = $this->importsModel->createImport($input);

            return $this->json([
                'import_id' => $importId
            ]);

        } catch (Exception $e) {

            return $this->json(
                [],
                'error',
                $e->getMessage()
            );

        }
    }

    /**
     * Chi tiết phiếu nhập
     */
    function details()
    {
        $payload = $this->checkToken();

        $input = Input::all();

        $result = $this->importsModel->getImportItems(
            $input['id']
        );

        return $this->json($result);
    }
}