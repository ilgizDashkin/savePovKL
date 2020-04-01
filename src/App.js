import React, { Component, useState, useEffect, } from 'react';
// import bridge from '@vkontakte/vk-bridge';
// import View from '@vkontakte/vkui/dist/components/View/View';
// import ScreenSpinner from '@vkontakte/vkui/dist/components/ScreenSpinner/ScreenSpinner';
import '@vkontakte/vkui/dist/vkui.css';
import { View, Panel, PanelHeader, FormLayout, File, Div, Button, Input, Spinner, CardGrid, Card } from '@vkontakte/vkui';//пакеты из вк
import Icon24CameraOutline from '@vkontakte/icons/dist/24/camera_outline';
import Icon24Send from '@vkontakte/icons/dist/24/send';


class App extends Component {
	constructor(props) {
		super(props);
		this.state = {
			isLoading: false,
			nameKl: '',
			zamer: '',
			where: '',
			who: '',
			selectedFile: null,
			result_serv: null
		}

	}

	componentDidMount() {
		const lastState = localStorage.savepovkl
		if (lastState) {
			// console.log(lastState)
			this.setState(JSON.parse(lastState))
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
	onChangeHandler = event => {
		// console.log(event.target.files[0])
		this.setState({
			selectedFile: event.target.files			
		})
	}
	onClickHandler = async () => {
		const data = new FormData()
		// data.append('foto1', this.state.selectedFile)
		if ((this.state.selectedFile) && (this.state.selectedFile.length <= 3)) {
			this.setState({ isLoading: true }) //пока грузится показываем спинер
			for (let x = 1; x <= this.state.selectedFile.length; x++) {
				data.append('foto' + x, this.state.selectedFile[x - 1])
			}
			// data.append('foto1', this.state.selectedFile[0])
			data.append('name', this.state.nameKl)
			data.append('zamer', this.state.zamer)
			data.append('otkuda', this.state.where)
			data.append('kto', this.state.who)
			let response = await fetch('https://ilgiz.h1n.ru/savepovkl.php', {
				method: 'POST',
				body: data
			});

			let result = await response.json()
			this.setState({
				isLoading: false,
				result_serv: result,
			})
			// console.log(result);
			localStorage.savepovkl = JSON.stringify(this.state);//сохраняем стейт в локалсторадже
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
							<FormLayout align="center">
								<Input type="text" top="наименование КЛ" placeholder='введите название КЛ' align="center" value={this.state.nameKl} onChange={this.nameKlChange} />
								<Input type="number" top="замер" placeholder='введите замер' align="center" value={this.state.zamer} onChange={this.zamerChange} />
								<Input type="text" top="откуда замер" placeholder='введите откуда замер' align="center" value={this.state.where} onChange={this.whereChange} />
								<Input type="text" top="кто искал" placeholder='введите кто искал' align="center" value={this.state.who} onChange={this.whoChange} />
								<File multiple onChange={this.onChangeHandler} top="фото места повреждения" before={<Icon24CameraOutline />} size="l">
									можно выбрать не более 3-х фото
                                </File>
								<p className=" text-white">
									количество выбранных фото:
									{
										this.state.selectedFile ?
											(this.state.selectedFile.length > 3 ? <span className='bg-danger'> больше 3</span> : ` ${this.state.selectedFile.length||''}`)
											: null
									}
								</p>
								<Button onClick={this.onClickHandler} before={<Icon24Send />} size="l">отправить</Button>
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

