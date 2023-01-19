<?php

namespace App\Http\Livewire;
use Livewire\Component;
use App\Models\Location;

use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;


class MapLocation extends Component
{

    public $locationId,$long,$lat,$title,$description,$image;
    public $geoJson;
    public $imageUrl;
    public $idEdit = false;

    use WithFileUploads;

    private function loadLokasi() {
        $locations = Location::orderBy('created_at', 'desc')->get();
        $customLoc = [];
        foreach ($locations as $l) {
            $customLoc[] = [
               'type' => 'Feature',
               'geometry' => [
                    'coordinates' => [$l->long, $l->lat],
                    'type' => 'Point'
               ],
               'properties' => [
                    'locationId' => $l->id,
                    'title' => $l->title,
                    'image' => $l->image,
                    'description' => $l->description,
               ]
            ];
        }
        $geoLoc = [
            'type' => 'FeatureCollection',
            'features' => $customLoc
        ];

        $geoJson = collect($geoLoc)->toJson();

        $this->geoJson = $geoJson;
    }

    public function render()
    {
        $this->loadLokasi();
        return view('livewire.map-location');
    }

    private function resetForm() {
        $this->long = '';
        $this->lat = '';
        $this->title = '';
        $this->description = '';
        $this->image = '';
}

    public function simpanLoc() {
        $this->validate([
            'long' => 'required',
            'lat' => 'required',
            'title' => 'required',
            'description' => 'required',
            'image' => 'image|max:5000|required',
        ]);

        $imageNama = md5($this->image.microtime()).'.'.$this->image->extension(); //mencegah nama file yang sama

        Storage::putFileAs(
            'public/images', //tempat penyimpanan
            $this->image, //Sumber File nya
            $imageNama // Nama File
        );

        // Diambil dari public Variabel yang telah ditentukan
        Location::create([
            'long' => $this->long,
            'lat' => $this->lat,
            'title' => $this->title,
            'description' => $this->description,
            'image' => $imageNama,
        ]);

        $this->resetForm();
        $this->loadLokasi(); // Untuk ngerefresh map
        $this->dispatchBrowserEvent('locationTambah', $this->geoJson); // Mengirim dan mengupdate data Json
    }

    

    public function cariLocationId($id) {
        $location = Location::findOrFail($id);
        
        $this->locationId = $id;
        $this->long = $location->long;
        $this->lat = $location->lat;
        $this->title = $location->title;
        $this->description = $location->description;
        $this->imageUrl = $location->image;
        $this->idEdit = true;
    }

    public function updateLoc() {
        $this->validate([
            'long' => 'required',
            'lat' => 'required',
            'title' => 'required',
            'description' => 'required',
        ]);

        $location = Location::findOrFail($this->locationId);
        if($this->image){
            $imageNama = md5($this->image.microtime()).'.'.$this->image->extension(); //mencegah nama file yang sama

        Storage::putFileAs(
            'public/images', //tempat penyimpanan
            $this->image, //Sumber File nya
            $imageNama // Nama File
        );

        $updateData = [
            'title' => $this->title,
            'description' => $this->description,
            'image' => $imageNama
        ];
        }else{
            $updateData = [
                'title' => $this->title,
                'description' => $this->description,
            ];
        }

        $location->update($updateData);

        $this->imageUrl = "";
        $this->resetForm();
        $this->loadLokasi(); // Untuk ngerefresh map
        $this->dispatchBrowserEvent('updateLoc', $this->geoJson); // Mengirim dan mengupdate data Json
    }

    function hapusLokasi() {
        $location = Location::findOrFail($this->locationId);
        $location->delete();

        $this->imageUrl = "";
        $this->resetForm();
        $this->idEdit = false;
        $this->dispatchBrowserEvent('hapusLokasi', $location->id); // Mengirim dan mengupdate data Json
    }

}
