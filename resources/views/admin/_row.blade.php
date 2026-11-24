@php
    /** @var \App\Models\User $user */
@endphp
<div class="stu">
    <div class="nm">
        <b>{{ $user->name }}</b>
        <span class="small muted">{{ $sub }} ·
            {{ $user->using_default_password ? 'password bawaan' : 'password sudah diganti' }}</span>
    </div>
    <form method="POST" action="{{ route('admin.reset', $user) }}">
        @csrf
        <button class="btn sm ghost" type="submit" @disabled($user->using_default_password)>Reset</button>
    </form>
</div>
