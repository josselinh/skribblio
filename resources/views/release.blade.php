@extends('layouts.app')

@section('content')
    <div class="max-w-3xl mx-auto space-y-6">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="border-b border-gray-200 bg-gray-50 px-6 py-4">
                <h3 class="text-base font-semibold text-indigo-600">2020-04-13 - v1.0.4</h3>
            </div>
            <div class="px-6 py-4 text-sm text-gray-700 space-y-2">
                <p>Ajout de fontawesome</p>
                <p>Ajout d'une règle d'unicité sur les phrases</p>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="border-b border-gray-200 bg-gray-50 px-6 py-4">
                <h3 class="text-base font-semibold text-indigo-600">2020-04-06 - v1.0.3</h3>
            </div>
            <div class="px-6 py-4 text-sm text-gray-700 space-y-2">
                <p>Mise en place du système de vote</p>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="border-b border-gray-200 bg-gray-50 px-6 py-4">
                <h3 class="text-base font-semibold text-indigo-600">2020-04-05 - v1.0.2</h3>
            </div>
            <div class="px-6 py-4 text-sm text-gray-700 space-y-2">
                <p>Amélioration du CSS</p>
                <p>Pagination sur la page qui liste les phrases</p>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="border-b border-gray-200 bg-gray-50 px-6 py-4">
                <h3 class="text-base font-semibold text-indigo-600">2020-04-05 - v1.0.1</h3>
            </div>
            <div class="px-6 py-4 text-sm text-gray-700 space-y-2">
                <p>Amélioration de la top navbar</p>
                <p>Séparation de l'ajout d'un groupe dans une page dédiée</p>
                <p>Séparation de l'ajout d'une phrase dans une page dédiée</p>
                <p>Possibilité de faire un groupe :</p>
                <ul class="list-disc list-inside space-y-1 ms-2">
                    <li>public (visible et modifiable par tout le monde)</li>
                    <li>public non modifiable (visible mais non modifiable)</li>
                    <li>privé (seul vous voyez ce groupe)</li>
                </ul>
                <p>Ajout de règles pour l'ajout d'un groupe et d'une phrase</p>
                <p>Amélioration de la recherche d'une phrase</p>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="border-b border-gray-200 bg-gray-50 px-6 py-4">
                <h3 class="text-base font-semibold text-indigo-600">2020-04-03 - v1.0.0</h3>
            </div>
            <div class="px-6 py-4 text-sm text-gray-700 space-y-2">
                <p>Possibilité d'ajouter des groupes</p>
                <p>Possibilité d'ajouter des phrases</p>
                <p>Possibilité d'exporter une liste</p>
            </div>
        </div>
    </div>
@endsection
