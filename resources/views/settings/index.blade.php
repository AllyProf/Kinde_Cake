@extends('layouts.app')

@section('title', 'Settings')

@section('content')
  <div class="app-title">
    <div>
      <h1><i class="fa fa-cog"></i> Settings</h1>
      <p>Manage your business, appearance, categories, and package units</p>
    </div>
    <ul class="app-breadcrumb breadcrumb">
      <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
      <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
      <li class="breadcrumb-item">Settings</li>
    </ul>
  </div>

  @include('settings.partials._tabs')

  @if($tab === 'business')
    @include('settings.partials._business')
  @elseif($tab === 'appearance')
    @include('settings.partials._appearance')
  @elseif($tab === 'categories')
    @include('settings.partials._categories')
  @elseif($tab === 'payments')
    @include('settings.partials._payments')
  @elseif($tab === 'sms')
    @include('settings.partials._sms')
  @else
    @include('settings.partials._packages')
  @endif
@endsection
