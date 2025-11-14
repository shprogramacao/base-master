<?php

namespace AndersonScherdovski\Base;

use AndersonScherdovski\Base\Scopes\FilterScope;
use AndersonScherdovski\Base\Scopes\OrderScope;
use AndersonScherdovski\Base\Services\FileSystemService;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;

class  BaseController extends Controller
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    protected $canCache = false;
    protected $cacheName;
    protected $model;
    protected $with = [];
    protected $validator;
    protected $returnResponse = true;
    protected $filterClientId = false;
    protected $updateWithClientId = true;
    protected $webServiceMessage = true;
    protected $clientId;
    protected $userId;
    protected $userName;

    /**
     * Return index to paginate.
     * @hideFromAPIDocumentation
     * @return JsonResponse|mixed
     *
     */
    function index()
    {
        $all = null;
        if ($this->canCache) {
            $all = Cache::get($this->cacheName);
        }

        if (!$all) {
            $limit = request()->get('limit', 100);

            $this->model->addGlobalScope(new FilterScope());
            $this->model->addGlobalScope(new OrderScope());
            $model = $this->model;
            if ($this->filterClientId) {
                $clientId = request()->user()->client_id;
                $model = $this->model->where('client_id', $clientId);
            }
            $model = $model->with($this->with);

            if ($limit && $limit != 'null') {
                $all = $model->paginate($limit);
            } else {
                $all = $model->get();
            }
        }


        if ($this->canCache) {
            Cache::add($this->cacheName, $all);
        }

        if ($this->returnResponse) {
            return $this->successResponse($all, 200);
        }
        return $all;
    }

    /**
     * Count items.
     * @param $withCount
     */
    function addWithCount($withCount)
    {
        $this->model->withCount($withCount);
    }

    /**
     * Set filter.
     * @param $filter
     */
    function addFilter($filter)
    {
        $filters = request()->get('filters');
        $filters[] = $filter;
        request()->merge(['filters' => $filters]);
    }

    /**
     * Set filter.
     * @param $filter
     */
    function removeFilter($filter)
    {
        $filters = request()->get('filters');
        unset($filters[$filter]);;
        request()->merge(['filters' => $filters]);
    }

    /**
     * Add items.
     * @param $key
     * @param $value
     */
    function add($key, $value)
    {
        $items = request()->all();
        $items[$key] = $value;

        request()->merge($items);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param $request
     *
     * @return object
     */
    public function store(Request $request)
    {
        $allFields = $request->all();

        $clientId = null;
        if ($this->updateWithClientId) {
            $clientId = request()->get('client_id');
            $this->add('client_id', $clientId);
        }

        if ($this->webServiceMessage) {
            $clientId = $request->get('client_id');
            $this->add('client_id', $clientId);
        }

        if ($this->validator) {
            $validator = App::make($this->validator);
            $allFields = $validator->validated();
        }

        $nameFiles = $this->model->files ? $this->model->files : [];

        $allFieldsCreate = [];
        foreach ($allFields as $key => $value) {

            if (!in_array($key, $nameFiles)) {
                $allFieldsCreate[$key] = $value;
            }
        }


        $item = $this->model->create($allFieldsCreate);
        foreach ($allFields as $key => $value) {
            if (!in_array($key, $nameFiles)) {
                continue;
            }

            if (!$value) {
                continue;
            }

            $idSave =  $item->id;
            if ($clientId) {
                $idSave = $clientId;
            }
            $path = FileSystemService::uploadBase64File($value, 'clients/' .   $idSave . '/documents', $item[$key]);
            $allFields[$key] = $path;
        }
        $item->update($allFields);


        if ($this->returnResponse) {
            return $this->returnItem($item);
        } else {
            return $item;
        }
    }

    /**
     * Return item.
     *
     * @param $item
     * @return JsonResponse|mixed
     */
    function returnItem($item)
    {
        $index = request()->get('index', false);
        if ($index) {
            return $this->index();
        } else {
            return response()->json($item);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param $id
     * @return JsonResponse
     */
    public function update(Request $request, $id)
    {
        $allFields = $request->all();
        if ($this->validator) {
            $validator = App::make($this->validator);
            $allFields = $validator->validated();
        }

        $item = $this->model->find($id);
        if(isset($item->files)){
            foreach ($allFields as $key => $value) {
                if (!in_array($key, $item->files)) {
                    continue;
                }

                if (!$value) {
                    continue;
                }

                try {
                    $path = FileSystemService::uploadBase64File($value, 'clients/' .  $item->id . '/documents', $item[$key]);
                    $allFields[$key] = $path;
                } catch (\Throwable $th) {
                }
            }
        }
        $item->update($allFields);
        $item = $this->model->find($id);

        $index = request()->get('index', false);
        if ($index) {
            return $this->index();
        } else {
            return response()->json($item, 201);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param $id
     *
     * @return JsonResponse
     */
    public function show($id)
    {
        $item = $this->model->with($this->with)->find($id);
        return response()->json($item);
    }

    /**
     *  Update multiple messages in the database
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function bulkUpdate(Request $request)
    {
        $data = $request->get('data');

        foreach ($data as $key => $item) {
            $this->model->find($key)->update($item);
        }
        $index = request()->get('index', false);
        if ($index) {
            return $this->index();
        } else {
            return response()->json('Alterado com sucesso', 201);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return JsonResponse
     *
     * @throws Exception
     */
    public function destroy($id)
    {
        $item = $this->model->find($id);
        $item->delete();

        $index = request()->get('index', false);
        if ($index) {
            return $this->index();
        } else {
            return response()->json('Deletado com sucesso', 202);
        }
    }

    /**
     * Delete multiple messages in the database
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function bulkDelete(Request $request)
    {
        $ids = $request->get('ids');
        foreach ($ids as $id) {
            $this->model->find($id)->delete();
        }

        $index = request()->get('index', false);
        if ($index) {
            return $this->index();
        } else {
            return response()->json('Deletado com sucesso', 202);
        }
    }

    /**
     * Delete multiple messages in the database
     *
     * @return JsonResponse
     */
    public function fetchPair()
    {
        $this->returnResponse = false;
        $this->add("limit", null);
        $all = $this->index();

        $data = [];
        foreach ($all as $item) {

            $data[] = [
                'key' => $item->id,
                'value' => $item[$item->pairName]
            ];
        }

        return response()->json($data);
    }

    /**
     * Retorna uma resposta de sucesso.
     *
     * @param $data , $code
     * @param int $code
     * @return JsonResponse
     */
    protected function successResponse($data, $code = 200)
    {
        return response()->json($data, $code);
    }

    /**
     * Retorna uma resposta de erro.
     *
     * @param $message , $code
     * @return JsonResponse
     */
    protected function errorResponse($message, $code = 500)
    {
        return response()->json(['errors' => $message, 'code' => $code], $code);
    }
}
