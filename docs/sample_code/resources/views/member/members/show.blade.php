@extends('layouts.member')

@section('title', '会員公開情報')

@section('content')

<h1>会員公開情報</h1>
<x-alert/>




<div class="width-md table-vertical-responsive">
  <table>
    <tbody>
      <tr>
        <th>
          公開名
        </th>
        <td>
          {{ $member->nickname }}
        </td>
      </tr>
      <tr>
        <th>アイコン</th>
        <td>
          <img src="{{ $member->member_icon_url }}" class="icon">
        </td>
      </tr>

      <tr>
        <th>
          登録日
        </th>
        <td>
          {{ $member->created_at->format('Y年n月j日') }}
        </td>
      </tr>
    </tbody>
  </table>
</div>

@endsection