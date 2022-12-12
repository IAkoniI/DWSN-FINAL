<?php

namespace App\Http\Controllers;

use App\Models\File;
use App\Models\News;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class NewsController extends Controller
{
    public function dashboard(Request $request) {
        $news = News::where('user_id', Auth::user()->id)
            ->where('title', 'LIKE', '%'.$request->search.'%')
            ->orWhere('content', 'LIKE', '%'.$request->search.'%')
            ->orderBy('created_at', 'DESC')
            ->paginate(3);
        return view('dashboard', compact('news'));
    }

    public function create(Request $request) {
        $request->validate([
            'title' => 'required',
            'content' => 'required',
            'image' => 'required',
        ],[
            'required' => 'O campo :attribute é obrigatório!'
        ]);

        $news = $request->except('_token');
        $news['user_id'] = Auth::user()->id;
        News::create($news);

        return back()->with(['success' => 'Notícia criada com sucesso!']);
    }

    public function update(Request $request) {
        $request->validate([
            'title' => 'required',
            'content' => 'required',
            'image' => 'required',
        ],[
            'required' => 'O campo :attribute é obrigatório!'
        ]);

        $news = $request->except('_token');

        News::find($request->id)->update($news);

        return back()->with(['success' => 'Notícia editada com sucesso!']);
    }

    public function delete(Request $request) {
        // Excluir arquivos relacionados à notícia
        $files = File::where('news_id', $request->id)->get();
        foreach ($files as $file) {
            if (Storage::exists($file->directory)) // Verificar se existe o arquivo
                Storage::delete($file->directory); // Excluir arquivo
        }

        News::find($request->id)->delete();

        return back()->with(['success' => 'Notícia excluída com sucesso!']);
    }

    public function uploadFile(Request $request) {
        $request->validate([
            'id' => 'required | numeric | exists:news,id',
            'file' => ['required', 'mimes:png,jpg,webp', 'max:1024']
        ],[
            'file.required' => 'Selecione um arquivo!',
            'file.mimes' => 'Os tipos de arquivo permitido é apenas png, jpg ou webp!',
        ]);

        $directory = $request->file->store('files');

        File::create(['news_id' => $request->id, 'directory' => $directory]);

        return back()->with(['success' => 'Arquivo salvo com sucesso!']);
    }

    public function deleteFile(Request $request) {
        $file = File::find($request->id);

        if (Storage::exists($file->directory)) // Verificar se existe o arquivo
            Storage::delete($file->directory); // Excluir arquivo

        // Ecluir dados na tabela
        $file->delete();

        return back()->with(['success' => 'Arquivo excluído com sucesso!']);
    }

    public function downloadFile(Request $request) {
        $file = File::find($request->id);

        return Storage::download($file->directory);
    }
}

