import React, { Component } from 'react';
import '@vkontakte/vkui/dist/vkui.css';
import { View, Panel, PanelHeader, FormLayout, File, Button, Input, Spinner, CardGrid, Card, Div } from '@vkontakte/vkui';//пакеты из вк
import Icon24CameraOutline from '@vkontakte/icons/dist/24/camera_outline';//это из https://vkcom.github.io/icons/#24/smile
import Icon24Send from '@vkontakte/icons/dist/24/send';
import Icon24View from '@vkontakte/icons/dist/24/view';
import Compressor from 'compressorjs';
import Exif from 'exif-js'

class App extends Component {
	constructor(props) {
		super(props);
		this.state = {
			isLoading: false,
			nameKl: '',
			zamer: '',
			where: '',
			who: '',
			selectedFile1: null,
			selectedFile2: null,
			selectedFile3: null,
			result_serv: null,
			file2: null
		}

	}

	componentDidMount() {
		//вызываем предыдущее состояние из локалсториджа
		const lastState = localStorage.savepovkl1
		if (lastState) {
			// console.log(lastState)
			this.setState({ who: JSON.parse(lastState) })
		}
	}

	//обязательно используем стрелочные фунции чтоб не прописывать методы в конструкторе
	nameKlChange = (event) => {
		this.setState({ nameKl: event.target.value.toUpperCase() });
	}
	zamerChange = (event) => {
		this.setState({ zamer: event.target.value });
	}
	whereChange = (event) => {
		this.setState({ where: event.target.value.toUpperCase() });
	}
	whoChange = (event) => {
		this.setState({ who: event.target.value.toUpperCase() });
	}
	onChangeHandler1 = event => {
		// console.log(event.target.files[0])
		this.setState({
			selectedFile1: event.target.files[0]
		})
	}
	onChangeHandler2 = event => {
		// console.log(event.target.files[0])
		this.setState({
			selectedFile2: event.target.files[0]
		})
	}
	onChangeHandler3 = event => {
		// console.log(event.target.files[0])
		this.setState({
			selectedFile3: event.target.files[0]
		})
	}
	//отправляем на сервер данные
	onClickHandler = async () => {
		const data = new FormData()
		if ((this.state.selectedFile1) || (this.state.selectedFile2) || (this.state.selectedFile3)) {
			this.setState({ isLoading: true }) //пока грузится показываем спинер			
			// if (this.state.selectedFile1) { data.append('foto1', this.state.selectedFile1) }//отправляем без сжатия для вывода gps из фото
			// if (this.state.selectedFile2) { data.append('foto2', this.state.selectedFile2) }
			// if (this.state.selectedFile3) { data.append('foto3', this.state.selectedFile3) }	

			function getExifFoto(file_foto1) {
				// ищем координаты в фото
				return new Promise((resolve, reject) => {
					Exif.getData(file_foto1, function () {
						const allMetaData = Exif.getAllTags(this);
						// console.log(allMetaData)//вывод всех данных из фото

						function ConvertDMSToDD(degrees, minutes, seconds, direction) {
							// приводим координаты к десятичному виду
							let dd = degrees + (minutes / 60) + (seconds / 3600);
							if (direction === "S" || direction === "W") {
								dd = dd * -1;
							}
							return dd;
						}

						const latDegree = allMetaData.GPSLatitude[0];
						const latMinute = allMetaData.GPSLatitude[1];
						const latSecond = allMetaData.GPSLatitude[2];
						const latDirection = allMetaData.GPSLatitudeRef;

						const latFinal = ConvertDMSToDD(latDegree, latMinute, latSecond, latDirection);
						// console.log(latFinal);

						// Calculate longitude decimal
						const lonDegree = allMetaData.GPSLongitude[0];
						const lonMinute = allMetaData.GPSLongitude[1];
						const lonSecond = allMetaData.GPSLongitude[2];
						const lonDirection = allMetaData.GPSLongitudeRef;

						const lonFinal = ConvertDMSToDD(lonDegree, lonMinute, lonSecond, lonDirection);
						// console.log(lonFinal);
						let dateStamp=''
						if (allMetaData.DateTime){
							dateStamp = allMetaData.DateTime.slice(0, 10)//возьмем 10 символов из даты
							data.append('date', dateStamp)//добавляем в форму
						}						
						data.append('gps', `${latFinal},${lonFinal}`)//добавляем в форму

						resolve(`Gps data ${latFinal},${lonFinal}, dateStamp ${dateStamp}`);//выводим в случае успеха
						reject(`Gps data error `)//выводим в случае неудачи
					});
				}).then(
					response => console.log(`Exif : ${response}`),
					error => console.log(`Not Exif: ${error}`)
				);
			}

			function compressFoto(file, fileName) {
				// сжимаем фото2 и добавляем в форму с помощью промисов
				return new Promise((resolve, reject) => {
					new Compressor(file, {
						quality: 0.6,
						maxWidth: 1600,
						maxHeight: 1600,
						success(result) {
							let reader = new FileReader();
							reader.readAsDataURL(result);//результат сжатия считываем в басе64 строку
							reader.onloadend = function () {
								let base64data = reader.result;//result будет содержать данные как URL, представляющий файл, кодированый в base64 строку
								data.append(fileName, base64data)//добавляем в форму
								resolve(`Compress success ${fileName}`);//выводим в случае успеха
								reject(`Compress error ${fileName}`)//выводим в случае неудачи
							}
						},
					});
				}).then(
					response => console.log(`фото сжали добавили в FormData(): ${response}`),
					error => console.log(`не удалось сжать фото: ${error}`)
				);
			};

			Promise.all([
				getExifFoto(this.state.selectedFile1),
				compressFoto(this.state.selectedFile1, 'foto1'),
				compressFoto(this.state.selectedFile2, 'foto2'),
				compressFoto(this.state.selectedFile3, 'foto3')
			]).finally(async () => {//после запуска всех промисов с фото добавляем оставшиеся данные и отправляем н сервер
				data.append('name', this.state.nameKl)
				data.append('zamer', this.state.zamer)
				data.append('otkuda', this.state.where)
				data.append('kto', this.state.who)
				let response = await fetch('https://ilgiz.h1n.ru/savepovkl1.php', {
					method: 'POST',
					body: data
				});

				let result = await response.json()
				// let result = await response.text()//если ошибка можно глянуть в тексте ответа
				this.setState({
					isLoading: false,
					result_serv: result,
				})
				// console.log(result);
				localStorage.savepovkl1 = JSON.stringify(this.state.who);//сохраняем кто искал в локалсторадже
			});
		} else {
			alert('пожалуйста выберите от 1 до 3 фото')
		}
	}

	render() {
		return (
			<View id="view" activePanel="panel">
				<Panel id="panel">
					<PanelHeader>добавить привязку КЛ</PanelHeader>
					<div className="container bg-dark text-center ">
						<div className='container p-2'>
							<a type="button" className="btn btn-danger btn-lg btn-block" href='https://ilgiz.h1n.ru/index.php'>на главную</a>
							<FormLayout align="center">
								<Input type="text" top="наименование КЛ" placeholder='введите название КЛ' align="center" value={this.state.nameKl} onChange={this.nameKlChange} />
								<Input type="number" top="замер" placeholder='введите замер' align="center" value={this.state.zamer} onChange={this.zamerChange} />
								<Input type="text" top="откуда замер" placeholder='введите откуда замер' align="center" value={this.state.where} onChange={this.whereChange} />
								<Input type="text" top="кто искал" placeholder='введите кто искал' align="center" value={this.state.who} onChange={this.whoChange} />
								<Div style={{ display: 'flex' }}>
									<File  stretched onChange={this.onChangeHandler1} top="(для определения координат не забудьте включить геотеги на камере телефона!)" before={<Icon24CameraOutline />} size="l">
										фото 1 (с геотегами) {this.state.selectedFile1 ? this.state.selectedFile1.name : 'не выбрано'}
									</File>
								</Div>

								<Div style={{ display: 'flex' }}>
									<File  stretched onChange={this.onChangeHandler2} before={<Icon24CameraOutline />} size="l">
										фото 2  {this.state.selectedFile2 ? this.state.selectedFile2.name : 'не выбрано'}
									</File>
								</Div>

								<Div style={{ display: 'flex' }}>
									<File  stretched onChange={this.onChangeHandler3} before={<Icon24CameraOutline />} size="l">
										фото 3  {this.state.selectedFile3 ? this.state.selectedFile3.name : 'не выбрано'}
									</File>
								</Div>

								<Div style={{ display: 'flex' }}>
									<Button stretched onClick={this.onClickHandler} before={<Icon24Send />} size="l">отправить</Button>
								</Div>

								{
									this.state.isLoading ?
										<div style={{ display: 'flex', alignItems: 'center', flexDirection: 'column' }}>
											<Spinner size="large" style={{ marginTop: 20 }} />
										</div> :
										<CardGrid>
											<Card size="l" mode="outline">
												{
													this.state.result_serv ?
														`привязка КЛ ${this.state.result_serv.name} сохранена.
								                    ${this.state.result_serv.file1} ${this.state.result_serv.file2 || ''} ${this.state.result_serv.file3 || ''}
								                    ${this.state.result_serv.file1geo} Спасибо за работу! :)` :
														null
												}
											</Card>
											{
												this.state.result_serv ?
													// true?
													<Div style={{ display: 'flex' }}>
														<Button onClick={this.prevView} stretched before={<Icon24View />} size="l" href='https://ilgiz.h1n.ru/smotrnewpov/index.html'>галерея</Button>
													</Div> :
													null
											}
										</CardGrid>
								}
							</FormLayout>
						</div>
					</div>
				</Panel>
			</View>
		);
	}
}

export default App;

