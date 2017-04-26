<script>
    $(document).ready(function(e) {
        $(document).on('click', '.mission-note-delete', function(event) {
            var caller = $(this);

            $.ajax({
                type: 'DELETE',
                url: '{{ url('/hub/missions/'.$mission->id.'/notes') }}/' + caller.data('id'),
                success: function(data) {
                    caller.parents('.mission-comment-item').remove();
                }
            });

            event.preventDefault();
        });

        $('#submit-mission-comment').submit(function(event) {
            var form = $(this);

            $.ajax({
                type: 'POST',
                url: '{{ url('/hub/missions/'.$mission->id.'/notes') }}',
                data: form.serialize(),
                beforeSend: function() {
                    $('*').blur();
                },
                success: function(data) {
                    $('.mission-comments').append(data);
                    $('.has-mentions').html('');
                    form[0].reset();
                }
            });

            event.preventDefault();
        });

        $('.has-mentions').mentions([{
            trigger: '@',
            pool: 'users',
            display: 'username',
            reference: 'id'
        }]);
    });
</script>

<div class="mission-comments">
    <div class="alert alert-info pull-left w-100 m-b-3 m-t-0" role="alert">
        Submit notes back to the mission maker on how to improve their mission. Anything that is <strong>game breaking</strong> should be noted clearly.<br />
        The mission maker will receive a notification for new notes on their mission; and testers will receive notifications for all notes on all missions.
    </div>

    @foreach ($mission->notes as $note)
        @include('missions.notes.item', ['note' => $note])
    @endforeach
</div>

<div class="mission-comments-form pull-left w-100">
    <form method="post" id="submit-mission-comment">
        <input type="hidden" name="mentions" value="">
        <textarea class="form-control" name="text" style="display:none"></textarea>

        <div
            class="form-control-editable has-mentions mission-aar-textarea form-control m-b-3 m-t-3"
            contenteditable="plaintext-only"
            for="text"></div>

        <button type="submit" class="btn btn-raised btn-primary pull-right m-r-3">Submit</button>
    </form>
</div>
