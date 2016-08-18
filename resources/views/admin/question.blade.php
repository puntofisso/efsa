@extends('layouts.app')

@section('content')


<div class="container">
    <div class="row">
        <div class="col-md-10 col-md-offset-1">
            <div class="panel panel-default">
                <div class="panel-heading">Update Question {{ $question -> QUESTIONNUMBER }}</div>

                <div class="panel-body">
                    <form id="form" method="GET">
                        <h3>Status: <b>{{ $question->STATUS }}</b></h3>
                        
                        <select id="STATUS" name="STATUS"> 
                            <option>Finished</option>
                            <option>In progress</option>
                            <option>Additional data request</option>
                            <option>Withdrawn</option>
                            <option>Under Consideration</option>
                            <option>Deleted</option>
                            <option>Waiting for full dossier</option>
                            <option>Not accepted</option>
                            <option>Registration not yet completed</option>
                        </select>
                        <hr/>
                        <input id='btn' name='btn' value="Save" type="submit" onClick="clickbtn();"></input>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    
    document.getElementById("btn").addEventListener("clickbtn", function(event){
        event.preventDefault()
    });

    function clickbtn() {
        event.preventDefault();
                
        var select = document.getElementById("STATUS");
        var field = select.options[select.selectedIndex].value;

        var url = "/admin/question/update/{{ $question->QUESTIONNUMBER }}/STATUS/"+ field;

        window.location.href = url;
    }



</script>

@endsection
