<?php

namespace App\Http\Controllers\Missions;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Missions\Mission;
use App\Models\Missions\MissionComment;
use App\Notifications\MissionCommentAdded;
use App\Models\Portal\User;
use Notification;

class CommentController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if (!$request->exists('mission_id')) {
            abort(403, 'You must pass the mission ID in the URL arguments');
            return;
        }

        $comments = Mission::find($request->mission_id)->comments;

        return view('missions.comments.list', compact('comments'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (strlen(trim($request->text)) == 0) {
            abort(403, 'No comment text provided');
            return;
        }

        if ($request->id == -1) {
            // Create a new comment
            $comment = new MissionComment();
            $comment->mission_id = $request->mission_id;
            $comment->user_id = auth()->user()->id;
            $comment->text = $request->text;
            $comment->published = $request->published;
            $comment->save();

            $mission = Mission::findOrFail($request->mission_id);

            if ($comment->published) {
                static::notify($mission, $comment);
            }
        } else {
            // Update an existing one
            $comment = MissionComment::find($request->id);
            $was_published = $comment->published;

            $comment->text = $request->text;
            $comment->published = $request->published;
            $comment->save();

            if (!$was_published) {
                static::notify($comment->mission, $comment);
            }
        }

        if ($comment->published) {
            return view('missions.comments.item', compact('comment'));
        } else {
            return $comment->id;
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(MissionComment $comment)
    {
        return $comment->text;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(MissionComment $comment)
    {
        $comment->delete();
    }

    /**
     * Notifies all users of a new comment.
     *
     * @return any
     */
    public static function notify(Mission $mission, MissionComment $comment)
    {
        $users = User::where('id', '!=', auth()->user()->id)->get();
        Notification::send($users, new MissionCommentAdded($comment));
    }
}
