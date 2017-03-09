<script>
    $(document).ready(function(e) {
        var slot = null;

        $('.operation-item-mission-item').click(function(event) {
            var caller = $(this);
            var isAssigned = caller.hasClass('assigned');

            if (isAssigned) {
                $.ajax({
                    type: 'POST',
                    url: '{{ url('/hub/missions/operations/remove-mission') }}',
                    data: {'id': caller.data('item') || -1},
                    success: function(data) {
                        caller.html('Assign Mission');
                        caller.removeClass('assigned');
                        caller.addClass('unassigned');
                    }
                });

                return;
            }

            $('.operation-item-mission-item.unassigned').html('Assign Mission');

            caller.html('Pick a mission below');
            slot = caller;

            event.preventDefault();
        });

        $('.mission-item').click(function(event) {
            event.preventDefault();

            var caller = $(this);
            var mission_id = caller.data('id');
            var operation_id = slot.parents('.operation-item').data('id');
            var order = slot.data('order');
            
            if (slot != null) {
                $.ajax({
                    type: 'POST',
                    url: '{{ url('/hub/missions/operations/add-mission') }}',
                    data: {
                        'mission_id': mission_id,
                        'operation_id': operation_id,
                        'play_order': order
                    },
                    success: function(data) {
                        slot.data('mission', mission_id);
                        slot.data('item', data.trim());
                        slot.html('<b>' + caller.find('.mission-item-title').html() + '</b>');
                        slot.removeClass('unassigned');
                        slot.addClass('assigned');
                    }
                });
            }
        });
    });
</script>

@php
    use App\Models\Operations\Operation;
    use App\Models\Operations\OperationMission;
    use App\Models\Missions\Mission;
@endphp

<div class="large-panel-content full-page" id="app">
    <h1 class="mt-0 mb-5">
        Operations

        <a href="javascript:void(0)" class="btn hub-btn btn-primary pull-right" @click="createOperation">Create Operation</a>
    </h1>

    <div class="operations">
        {{-- @foreach (Operation::orderBy('starts_at', 'desc')->take(4)->get() as $operation)
            <div class="operation-item" data-id="{{ $operation->id }}">
                <span class="operation-item-date">
                    {{ $operation->starts_at->format('jS F') }}
                </span>

                <span class="operation-item-time">
                    {{ $operation->starts_at->format('H:i') }}
                </span>

                <div class="operation-item-missions">
                    @for ($i = 1; $i <= 6; $i++)
                        @php
                            $item = OperationMission::where('play_order', $i)->where('operation_id', $operation->id)->first();
                        @endphp

                        @if (is_null($item))
                            <a
                                href="javascript:void(0)"
                                class="operation-item-mission-item unassigned"
                                data-item="-1"
                                data-mission="-1"
                                data-order="{{ $i }}">
                                Assign Mission
                            </a>
                        @else
                            <a
                                href="javascript:void(0)"
                                class="operation-item-mission-item assigned"
                                data-item="{{ $item->id }}"
                                data-mission="{{ $item->mission->id }}"
                                data-order="{{ $i }}">
                                <b>{{ $item->mission->display_name }}</b>
                            </a>
                        @endif
                    @endfor
                </div>
            </div>
        @endforeach --}}

        <div class="operation-item" v-for="(op, index) in operations">
            <tr>
                <td>@{{ op.id }}</td>
                <td>@{{ op.starts_at }}</td>
                <td><button @click="removeOperation(op)">&times;</button></td>
            </tr>
        </div>
    </div>
</div>

{{-- <div class="operations-mission-browser">
    <h2 class="mission-section-heading" style="margin-top: 0 !important">New Missions</h2>

    <ul class="mission-group">
        @foreach (Mission::allNew() as $mission)
            @include('missions.item', ['mission' => $mission])
        @endforeach
    </ul>

    <h2 class="mission-section-heading">Past Missions</h2>

    <ul class="mission-group">
        @foreach (Mission::allPast() as $mission)
            @include('missions.item', ['mission' => $mission])
        @endforeach
    </ul>
</div> --}}

<script>
    new Vue({
        el: '#app',

        data: {
            operations: []
        },

        created: function() {
            axios.get('/api/operations')
                .then(response => {
                    this.operations = response.data;
                })
                .catch(e => {
                    this.errors.push(e);
                });
        },

        methods: {
            createOperation: function() {
                axios.post('/api/operations')
                    .then(response => {
                        this.operations.push(response.data);
                    });
            },

            removeOperation: function(op) {
                axios.delete('/api/operations/' + op.id)
                    .then(response => {
                        this.operations.splice(this.operations.indexOf(op), 1);
                    });
            }
        }
    });
</script>
