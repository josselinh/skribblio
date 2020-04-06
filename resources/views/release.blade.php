@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">2020-04-06 - v1.0.3</h6>
            </div>
            <div class="card-body">
                <p>Mise en place du système de vote</p>
            </div>
        </div>

        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">2020-04-05 - v1.0.2</h6>
            </div>
            <div class="card-body">
                <p>Amélioration du CSS</p>
                <p>Pagination sur la page qui liste les phrases</p>
            </div>
        </div>

        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">2020-04-05 - v1.0.1</h6>
            </div>
            <div class="card-body">
                <p>Amélioration de la top navbar</p>
                <p>Séparation de l'ajout d'un groupe dans une page dédiée</p>
                <p>Séparation de l'ajout d'une phrase dans une page dédiée</p>
                <p>Possibilité de faire un groupe :
                <ul>
                    <li>public (visible et modifiable par tout le monde)</li>
                    <li>public non modifiable (visible mais non modifiable)</li>
                    <li>privé (seul vous voyez ce groupe)</li>
                </ul>
                </p>
                <p>Ajout de règles pour l'ajout d'un groupe et d'une phrase</p>
                <p>Amélioration de la recherche d'une phrase</p>
            </div>
        </div>

        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">2020-04-03 - v1.0.0</h6>
            </div>
            <div class="card-body">
                <p>Possibilité d'ajouter des groupes</p>
                <p>Possibilité d'ajouter des phrases</p>
                <p>Possibilité d'exporter une liste</p>
            </div>
        </div>
    </div>
@endsection
