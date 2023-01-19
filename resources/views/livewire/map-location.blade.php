<div>
    <h1>Geography Information System</h1>
    <div class="row">
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    FORM
                </div>
                <div class="card-body">
                    <form 
                            @if($idEdit)
                                wire:submit.prevent="updateLoc"
                            @else
                                wire:submit.prevent="simpanLoc"
                            @endif
                    >
                        <div class="row">
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label> Longtitude</label>
                                    <input wire:model="long" type="text" class="form-control" name="longtitude">
                                    @error($long)
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label> Lattitude</label>
                                    <input wire:model="lat" type="text" class="form-control" name="lattitude">
                                    @error($lat)
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label>Title</label>
                                    <input wire:model="title" type="text" class="form-control" name="title">
                                    @error($title)
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label>Description</label>
                                    <textarea wire:model="description" class="form-control" name="description"></textarea>
                                    @error($description)
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label>Image</label>
                                    <input wire:model="image" type="file" class="form-control" name="image">
                                    @error($title)
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                    @if ($image)
                                        <img src="{{ $image->temporaryUrl() }}" class="img-fluid" width="500px">
                                    @endif
                                    @if ($imageUrl && !$image)
                                        <img src="{{ asset('/storage/images'.$imageUrl) }}" class="img-fluid" width="500px">
                                    @endif
                                </div>
                            </div>

                            <div class="d-flex justify-content-end">
                                <button class="btn btn-success" type="submit">{{ $idEdit ? "Ubah Lokasi" : "Simpan Lokasi" }}</button>
                                @if ($idEdit)
                                    <button wire:click="hapusLokasi" class="btn btn-danger" type="button">Hapus Lokasi</button>
                                @endif
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    MAPS
                </div>
                <div class="card-body">
                    <div wire:ignore id="map" style="width: 200; height: 800px"></div>
                </div>
            </div>
        </div>

    </div>

    @push('scripts')
        <script>
            document.addEventListener('livewire:load', () => {
                const defLokasi = [106.83613174739708, -6.276816281136291] // posisikan Lokasi yang sudah ditetapkan 
                mapboxgl.accessToken = '{{ env('MAPBOX_KEY') }}'; // Variabel dari .env
                const map = new mapboxgl.Map({
                    container: 'map', // container ID
                    center: defLokasi, // Arahkan kordinat lokasi
                    zoom: 13,
                    style: 'mapbox://styles/mapbox/streets-v12', // style URL
                });

                const loadLokasi = (geoJson) => {
                    geoJson.features.forEach((lokasi) => {
                        const {
                            geometry,
                            properties
                        } = lokasi //di dapatkan dari variabel lokasi
                        const {
                            iconSize,
                            locationId,
                            title,
                            image,
                            description
                        } = properties // didapatkan dari properties

                        let tandaElement = document.createElement('div')
                        tandaElement.className = 'tanda' + locationId
                        tandaElement.id = locationId
                        tandaElement.style.backgroundImage =
                            'url(https://cdn.icon-icons.com/icons2/2444/PNG/512/location_map_pin_mark_icon_148685.png)'
                        tandaElement.style.backgroundSize = 'cover'
                        tandaElement.style.width = '55px'
                        tandaElement.style.height = '55px'

                        const imagePenyimpanan = '{{ asset("/storage/images") }}' + '/' + image

                        const konten = `
                <div style="overflow-y, auto;max-height:400px,width:100%">
                    <table class="table table-responsive">
                        <tbody>
                            <tr>
                                <td>Title : </td>
                                <td>${title}</td>
                            </tr>
                            <tr>
                                <td>Pict : </td>
                                <td><img src="${imagePenyimpanan}" loading="lazy" class="img-fluid" width="200px"></td>
                            </tr>
                            <tr>
                                <td>Description : </td>
                                <td>${description}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>`

                        tandaElement.addEventListener('click', (e) => {
                            const locationId = e.target.id
                            @this.cariLocationId(locationId)
                        })

                        const popUp = new mapboxgl.Popup({
                            offset: 25
                        }).setHTML(konten).setMaxWidth("400px")

                        new mapboxgl.Marker(tandaElement) // Tampilka tanda lokasi
                            .setLngLat(geometry.coordinates) // Tampilkan kordinat yang sudah ditambahkan
                            .setPopup(popUp)
                            .addTo(map)
                    })
                }
                loadLokasi({!! $geoJson !!})

                // diambil dari dispatchBrowserEvent
                window.addEventListener('locationTambah', (e) => {
                    loadLokasi(JSON.parse(e.detail))
                })

                // Edit Lokasi
                window.addEventListener('updateLoc', (e) => {
                    loadLokasi(JSON.parse(e.detail))
                    $('.mapboxgl-popup').remove()
                    // console.log("update")
                })

                // Hapus Lokasi
                window.addEventListener('hapusLokasi', (e) => {
                    $('.tanda' + e.detail).remove()
                    $('.mapboxgl-popup').remove()
                    // console.log("update")
                })

                // console.log('this value from livewire', @this.test);
                map.addControl(new mapboxgl.NavigationControl()) // Fitur tambahan Zoom in/out

                map.on('click', (e) => {
                    const longtitude = e.lngLat.lng
                    const lattitude = e.lngLat.lat

                    @this.long = longtitude
                    @this.lat = lattitude
                }) // Jika di klik, akan muncul kordinatnya
            })
        </script>
    @endpush
