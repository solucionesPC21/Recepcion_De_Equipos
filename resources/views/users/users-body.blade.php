@foreach($users as $user)
        <tr>
            <td>{{ $user->id }}</td>
            <td>{{ $user->nombre }}</td>
            <td>{{ $user->usuario }}</td>
            <td></td>
            <td>
                <form id="formBorrarUsuario_{{ $user->id }}" action="{{ url('/users/'.$user->id) }}" method="post" style="display: inline;">
                    @csrf
                    {{ method_field('DELETE') }}
                    <input type="submit" value="Borrar" class="btn btn-danger" onclick="return confirm('¿Quieres Borrar?')">
                </form>
                <a class="btn btn-info editar-user" data-user-id="{{ $user->id }}" href="#" onclick="editarUser(event, {{ $user->id }})">Editar</a>
            </td>
        </tr>
@endforeach