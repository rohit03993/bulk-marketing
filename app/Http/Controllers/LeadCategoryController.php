<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class LeadCategoryController extends Controller
{
    public function index()
    {
        $categories = Tag::query()
            ->where('type', 'telecaller_lead_category')
            ->orderBy('name')
            ->get(['id', 'name', 'type']);

        $counts = [];
        if ($categories->isNotEmpty()) {
            $rows = DB::table('student_tag')
                ->select('tag_id', DB::raw('count(*) as cnt'))
                ->whereIn('tag_id', $categories->pluck('id'))
                ->groupBy('tag_id')
                ->get();

            $counts = $rows->pluck('cnt', 'tag_id')->map(fn ($v) => (int) $v)->all();
        }

        return view('admin.lead-categories.index', [
            'categories' => $categories,
            'counts' => $counts,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                // tags.name is globally unique in the DB, so this prevents duplicates across types.
                // Tellcaller categories should be unique anyway.
                Rule::unique('tags', 'name'),
            ],
        ]);

        Tag::create([
            'name' => $data['name'],
            'type' => 'telecaller_lead_category',
        ]);

        return redirect()->route('admin.lead-categories.index')->with('success', __('Category added.'));
    }

    public function destroy(Tag $tag, Request $request)
    {
        abort_if($tag->type !== 'telecaller_lead_category', 404);

        $usedCount = DB::table('student_tag')
            ->where('tag_id', $tag->id)
            ->count();

        if ($usedCount > 0) {
            return back()->with('error', __('Cannot delete: this category is used by :count students.', ['count' => $usedCount]));
        }

        $tag->delete();

        return redirect()->route('admin.lead-categories.index')->with('success', __('Category deleted.'));
    }
}

