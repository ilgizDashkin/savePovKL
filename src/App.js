import React, { Component, useState, useEffect, } from 'react';
// import bridge from '@vkontakte/vk-bridge';
// import View from '@vkontakte/vkui/dist/components/View/View';
// import ScreenSpinner from '@vkontakte/vkui/dist/components/ScreenSpinner/ScreenSpinner';
import '@vkontakte/vkui/dist/vkui.css';
import { View, Panel, PanelHeader, FormLayout, File } from '@vkontakte/vkui';//пакеты из вк
import Icon24CameraOutline from '@vkontakte/icons/dist/24/camera_outline';



class App extends Component {
	constructor(props) {
		super(props);
		  this.state = {
			selectedFile: null
		  }
	   
	  }
	  
	onChangeHandler=event=>{
		// console.log(event.target.files[0])
		this.setState({
			selectedFile: event.target.files[0],
			loaded: 0,
		  })
	}
	onClickHandler = () => {
		const data = new FormData() 
		data.append('file', this.state.selectedFile)
	}

	render() {
		return (
			// <View activePanel={activePanel} popout={popout}>
			// 	<Home id='home' fetchedUser={fetchedUser} go={go} />
			// 	<Persik id='persik' go={go} />
			// </View>
			<View id="view" activePanel="panel">
				<Panel id="panel">
					<PanelHeader>добавить привязку КЛ</PanelHeader>
					<div className="container">
						<div className="row">
							<div className="col-md-6">				
									{/* <div className="form-group files">
										<label>Upload Your File </label>
										<input type="file" className="form-control" onChange={this.onChangeHandler}/>
									</div> */}
									<File onChange={this.onChangeHandler}/><p>{this.state.selectedFile?this.state.selectedFile.name:null}</p>
							<button className="btn btn-danger btn-lg btn-block" onClick={this.onClickHandler}>send</button>	
							</div>							
						</div>
					</div>
				</Panel>
			</View>
		);
	}
}

export default App;

