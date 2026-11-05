@extends('errors.minimal')

@section('code', '403')
@section('icon', '🔒')
@section('title', 'Acceso denegado')
@section('message')
    {{ (isset($exception) && $exception->getMessage() && $exception->getMessage() !== 'This action is unauthorized.') ? $exception->getMessage() : 'No tienes permisos para ver esta sección. Si crees que es un error, contacta al administrador.' }}
@endsection
