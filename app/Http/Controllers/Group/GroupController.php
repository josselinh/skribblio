<?php

namespace App\Http\Controllers\Group;

use App\Http\Controllers\Controller;
use App\Http\Requests\GroupAddRequest;
use App\Managers\GroupManager;
use App\Models\Group;
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
        $groups = $this->groupManager->search($request->only(['search', 'author']), auth()->id());

        return view('group.index', compact('groups'));
    }

    /**
     * @return View
     */
    public function add(): View
    {
        $visibilities = [
            Group::VISIBILITY_PUBLIC => __('group.visibility.public'),
            Group::VISIBILITY_PUBLIC_UNALTERABLE => __('group.visibility.public_unalterable'),
            Group::VISIBILITY_PRIVATE => __('group.visibility.private'),
        ];

        return view('group.add', compact('visibilities'));
    }

    /**
     * @param GroupAddRequest $request
     * @return RedirectResponse
     */
    public function doAdd(GroupAddRequest $request): RedirectResponse
    {
        $group = $this->groupManager->add($request->input('name'), $request->input('visibility'), auth()->id());

        return redirect()->route('group.index', ['author' => auth()->id()])->with('alert-success', __('group.doAdd.success'));
    }
}
