<?php

namespace App\Http\Controllers\Group;

use App\Http\Controllers\Controller;
use App\Managers\GroupManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GroupController extends Controller
{
    /**
     * @var GroupManager
     */
    private $groupManager;

    /**
     * GroupController constructor.
     */
    public function __construct()
    {
        $this->groupManager = new GroupManager();
    }

    /**
     * @param Request $request
     * @return View
     */
    public function index(Request $request): View
    {
        $groups = $this->groupManager->search($request->only(['search', 'author']));

        return view('group.index', compact('groups'));
    }

    /**
     * @param Request $request
     * @return RedirectResponse
     */
    public function doAdd(Request $request): RedirectResponse
    {
        $group = $this->groupManager->add($request->input('name'), auth()->id());

        return redirect()->route('group.index', ['author' => auth()->id()])->with('alert-success', __('group.doAdd.success'));
    }
}
