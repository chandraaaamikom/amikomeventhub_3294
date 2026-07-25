@extends('layouts.admin')
@section('content')<h1 class="mb-8 text-3xl font-black">Buat Kupon</h1><form method="POST" action="{{ route('admin.coupons.store') }}" class="max-w-2xl rounded-3xl bg-white p-8">@include('admin.coupons._form')</form>@endsection
