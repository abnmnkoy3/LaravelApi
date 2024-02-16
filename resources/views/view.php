<h1>{{ $p->id }}</h1>

<h2>{{ $p->name}}</h2>

<form method="post">
    {{method_field('PUT')}}
    {{csrf_field()}}

<input type="text" name="name" value="{{ $p->name}}">
<input type="submit">

</form>