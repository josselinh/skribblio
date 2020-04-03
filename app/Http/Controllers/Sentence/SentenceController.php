<?php

namespace App\Http\Controllers\Sentence;

use App\Http\Controllers\Controller;
use App\Http\Requests\SentenceAddRequest;
use App\Http\Requests\SentenceImportRequest;
use App\Managers\GroupManager;
use App\Managers\SentenceManager;
use App\Managers\UserManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SentenceController extends Controller
{
    /**
     * @var SentenceManager
     */
    private $sentenceManager;

    /**
     * SentenceController constructor.
     */
    public function __construct()
    {
        $this->middleware('can:sentence-import')->only(['import', 'importDo']);

        $this->sentenceManager = new SentenceManager();
    }

    /**
     * @param Request $request
     * @return View
     */
    public function index(Request $request): View
    {
        $groupManager = new GroupManager();
        $groups = $groupManager->search();

        $sentences = $this->sentenceManager->search($request->only(['search', 'group', 'author']));

        return view('sentence.index', compact('sentences', 'groups'));
    }

    /**
     * @param SentenceAddRequest $request
     * @return RedirectResponse
     */
    public function doAdd(SentenceAddRequest $request): RedirectResponse
    {
        $group = $this->sentenceManager->add($request->input('name'), $request->input('group'), auth()->id());

        return redirect()->route('sentence.index', ['author' => auth()->id()])->with('alert-success', __('sentence.doAdd.success'));
    }

    /**
     * @return View
     */
    public function import(): View
    {
        $groupManager = new GroupManager();
        $groups = $groupManager->search();

        return view('sentence.import', compact('groups'));
    }

    /**
     * @param SentenceImportRequest $request
     * @return RedirectResponse
     */
    public function doImport(SentenceImportRequest $request): RedirectResponse
    {
        $this->sentenceManager->import($request->input('sentences'), $request->input('group'), auth()->id());

        return redirect()->route('sentence.index', ['group' => $request->input('group')])->with('alert-success', __('sentence.doImport.success'));
    }

    /**
     * @param Request $request
     * @return View
     */
    public function export(Request $request): View
    {
        $sentences = collect();

        $groupManager = new GroupManager();
        $groups = $groupManager->search();

        $userManager = new UserManager();
        $users = $userManager->search();

        $filters = $request->only(['groups', 'users']);

        if (!empty($filters)) {
            $sentences = $this->sentenceManager->export($filters);
        }

        return view('sentence.export', compact('sentences', 'groups', 'users'));
    }
}
