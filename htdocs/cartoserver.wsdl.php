<?php
header("Content-Type: text/xml");
echo '<?xml version="1.0"?>';
echo "\n";
?>
<wsdl:definitions xmlns="http://schemas.xmlsoap.org/wsdl/" xmlns:soap12="http://schemas.xmlsoap.org/wsdl/soap12/" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:enc="http://www.w3.org/2003/05/soap-encoding" xmlns:tns="http://camptocamp.com/wsdl/cartoserver/" xmlns:types="http://camptocamp.com/cartoserver/xsd" xmlns:test="http://camptocamp.com/cartoserver" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:wsdl="http://schemas.xmlsoap.org/wsdl/" xmlns:enc11="http://schemas.xmlsoap.org/soap/encoding/" targetNamespace="http://camptocamp.com/wsdl/cartoserver/" name="CartoserverWsdl">
  <wsdl:types>
    <schema xmlns="http://www.w3.org/2001/XMLSchema" targetNamespace="http://camptocamp.com/cartoserver/xsd">
      <import namespace="http://schemas.xmlsoap.org/soap/encoding/"/>

      <complexType name="ArrayOfLayer">
        <complexContent>
          <restriction base="enc11:Array">
            <attribute ref="enc11:arrayType" wsdl:arrayType="types:Layer[]"/>
          </restriction>
        </complexContent>
      </complexType>

      <complexType name="ArrayOfLayerId">
        <complexContent>
          <restriction base="enc11:Array">
            <attribute ref="enc11:arrayType" wsdl:arrayType="xsd:string[]"/>
          </restriction>
        </complexContent>
      </complexType>

        

      <complexType name="MapInfo">
        <all>
          <element name="mapId" type="xsd:string"/>
          <element name="mapLabel" type="xsd:string"/>
          <element name="layers" type="types:ArrayOfLayer"/>
          <!-- <element name="initialMapState" type="types:InitialMapState"/> -->

          <element name="location" type="types:LocationResult"/>

          <!-- <element name="override" type="types:MapInfo"/> -->

        </all>
      </complexType>

      <complexType name="InitialMapState">
        <all>
          <element name="location" type="types:LocationResult"/>
          <element name="selectedLayers" type="types:ArrayOfLayerId"/>
        </all>
      </complexType>

      <complexType name="Layer">
        <all>
          <element name="id" type="xsd:string"/>
          <!-- <element name="index" type="xsd:int"/> -->
          <element name="label" type="xsd:string"/>

          <element name="selected" type="xsd:boolean"/>
        </all>
      </complexType>

      <complexType name="MapRequest">
        <all>
          <element name="mapId" type="xsd:string"/>
          <element name="images" type="types:Images"/>
          <element name="locationRequest" type="types:LocationRequest"/>
          <element name="layerSelectionRequest" type="types:ArrayOfLayerId"/>
        </all>
      </complexType>

      <complexType name="Bbox">
        <all>
          <element name="minx" type="xsd:double"/>
          <element name="miny" type="xsd:double"/>
          <element name="maxx" type="xsd:double"/>
          <element name="maxy" type="xsd:double"/>
        </all>
      </complexType>


      <complexType name="ImagePoint">
        <all>
          <element name="x" type="xsd:int"/>
          <element name="y" type="xsd:int"/>
        </all>
      </complexType>


      <simpleType name="LocationType">
        <restriction base="xsd:string">

          <enumeration value="bboxLocationRequest"/>
          <enumeration value="panLocationRequest"/>
          <enumeration value="zoomPointLocationRequest"/>
          <enumeration value="zoomRectangleLocationRequest"/>

          <!--
          <enumeration value="BBOX"/>
          <enumeration value="PAN"/>
          <enumeration value="ZOOM_POINT"/>
          <enumeration value="ZOOM_RECTANGLE"/>
          -->
        </restriction>
      </simpleType>


      <complexType name="LocationRequest">
        <all>
          <element name="locationType"  type="types:LocationType"/>

          <!--<choice>-->
            <element name="bboxLocationRequest" type="types:BboxLocationRequest" minOccurs="0"/>
            <element name="panLocationRequest" type="types:PanLocationRequest" minOccurs="0"/>
            <element name="zoomPointLocationRequest" type="types:ZoomPointLocationRequest" minOccurs="0"/>
            <!-- <element name="zoomRectangleLocationRequest" type="types:ZoomRectangleLocationRequest"/> -->
          <!--</choice>-->
        </all>
      </complexType>     

      <complexType name="BboxLocationRequest">
        <all>
          <element name="bbox" type="types:Bbox"/>
        </all>
      </complexType>

      <simpleType name="PanDirectionType">
        <restriction base="xsd:string">
          <enumeration value="VERTICAL_PAN_NORTH"/>
          <enumeration value="VERTICAL_PAN_NONE"/>
          <enumeration value="VERTICAL_PAN_SOUTH"/>

          <enumeration value="HORIZONTAL_PAN_WEST"/>
          <enumeration value="HORIZONTAL_PAN_NONE"/>
          <enumeration value="HORIZONTAL_PAN_EAST"/>
        </restriction>
      </simpleType>

      <complexType name="PanDirection">
        <all>
          <element name="verticalPan" type="types:PanDirectionType"/>
          <element name="horizontalPan" type="types:PanDirectionType"/>
        </all>
      </complexType>

      <complexType name="PanLocationRequest">
        <all>
          <element name="bbox" type="types:Bbox"/>
          <element name="panDirection" type="types:PanDirection"/>
        </all>
      </complexType>

      <simpleType name="ZoomDirection">
        <restriction base="xsd:string">
          <enumeration value="ZOOM_DIRECTION_IN"/>
          <enumeration value="ZOOM_DIRECTION_NONE"/>
          <enumeration value="ZOOM_DIRECTION_OUT"/>
        </restriction>
      </simpleType>

      <complexType name="ZoomPointLocationRequest">
        <all>
          <element name="bbox" type="types:Bbox"/>
          <element name="imagePoint" type="types:ImagePoint"/>
          <element name="zoomDirection" type="types:ZoomDirection"/>
        </all>
      </complexType>
     
      <!-- TODO ZOOM RECTANGLE -->

<!--

XXXXXXXXXXXXXXXXXXXX polymorphism does not work XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX


      <complexType name="RelativeLocationRequest">
        <complexContent mixed="false">
       <extension base="types:LocationRequest">
        <all>
          <element name="bbox" type="types:Bbox"/>
        </all>
       </extension>
        </complexContent>
      </complexType>


      <complexType name="BboxLocationRequest">
        <complexContent mixed="false">
       <extension base="types:RelativeLocationRequest" />
        </complexContent>
      </complexType>


      <simpleType name="PanDirectionType">
        <restriction base="xsd:string">
          <enumeration value="VERTICAL_PAN_NORTH"/>
          <enumeration value="VERTICAL_PAN_NONE"/>
          <enumeration value="VERTICAL_PAN_SOUTH"/>

          <enumeration value="HORIZONTAL_PAN_WEST"/>
          <enumeration value="HORIZONTAL_PAN_NONE"/>
          <enumeration value="HORIZONTAL_PAN_EAST"/>
        </restriction>
      </simpleType>

      <complexType name="PanDirection">
        <all>
          <element name="verticalPan" type="types:PanDirectionType"/>
          <element name="horizontalPan" type="types:PanDirectionType"/>
        </all>
      </complexType>

      <complexType name="PanLocationRequest">
        <complexContent mixed="false">
       <extension base="types:RelativeLocationRequest">
        <all>
          <element name="panDirection" type="types:PanDirection"/>
        </all>
       </extension>
        </complexContent>
      </complexType>

      <simpleType name="ZoomDirection">
        <restriction base="xsd:string">
          <enumeration value="ZOOM_DIRECTION_IN"/>
          <enumeration value="ZOOM_DIRECTION_NONE"/>
          <enumeration value="ZOOM_DIRECTION_OUT"/>
        </restriction>
      </simpleType>

      <complexType name="ZoomPointLocationRequest">
        <complexContent mixed="false">
       <extension base="types:RelativeLocationRequest">
        <all>
          <element name="imagePoint" type="types:ImagePoint"/>
          <element name="zoomDirection" type="types:ZoomDirection"/>
        </all>
       </extension>
        </complexContent>
      </complexType>
XXXXXXXXXXXXXXXXXXXX polymorphism does not work XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
-->

<!--
      <complexType name="LocationRequest2">
        <complexContent mixed="false">
          <extension base="types:LocationRequest">
            <sequence>

              <element minOccurs="0" maxOccurs="1" name="huhuhuhu" type="string" />

            </sequence>
          </extension>
        </complexContent>
      </complexType>

      <complexType name="LocationRequest3">
          <extension base="types:LocationRequest">
            <all>
              <element name="huhuhuhu" type="string" />
            </all>
          </extension>
      </complexType>
-->

      <complexType name="Images">
        <all>
          <element name="mainmap" type="types:Image"/>
          <element name="keymap" type="types:Image"/>
          <element name="scalebar" type="types:Image"/>
        </all>
      </complexType>

      <complexType name="Image">
        <all>
          <element name="isDrawn" type="xsd:boolean"/>
          <element name="path" type="xsd:string"/>
          <element name="width" type="xsd:int"/>
          <element name="height" type="xsd:int"/>
          <element name="format" type="xsd:string"/>
        </all>
      </complexType>

      <complexType name="LocationResult">
        <all>
           <element name="bbox" type="types:Bbox"/>
           <element name="scale" type="xsd:double"/>
        </all>
      </complexType>

      <complexType name="MapResult">
        <all>
          <element name="location" type="types:LocationResult"/>
          <element name="images" type="types:Images"/>
        </all>
      </complexType>

      <complexType name="A">
        <sequence>
          <element minOccurs="0" maxOccurs="1" name="a" type="string" />
        </sequence>
      </complexType>


      <complexType name="B">
        <complexContent mixed="false">
          <extension base="types:A">
            <sequence>

              <element minOccurs="0" maxOccurs="1" name="ccc" type="string" />

              <element minOccurs="0" maxOccurs="1" name="b" type="string" />
            </sequence>
          </extension>
        </complexContent>
      </complexType>

      <element name="A" nillable="true" type="types:A" />

    </schema>
  </wsdl:types>

  <wsdl:message name="getMapInfoRequest">
    <wsdl:part name="mapId" type="xsd:string"/>
  </wsdl:message>

  <wsdl:message name="getMapInfoResult">
    <wsdl:part name="return" type="types:MapInfo"/>
  </wsdl:message>

  <wsdl:message name="getMapResult">
    <wsdl:part name="return" type="types:MapResult"/>
  </wsdl:message>

  <wsdl:message name="getMapRequest">
    <wsdl:part name="mapRequest" type="types:MapRequest"/>
  </wsdl:message>

  <portType name="CartoserverPortType">
    <wsdl:operation name="getMap">
      <wsdl:input message="tns:getMapRequest"/>
      <wsdl:output message="tns:getMapResult"/>
    </wsdl:operation>
    <wsdl:operation name="getMapInfo">
      <wsdl:input message="tns:getMapInfoRequest"/>
      <wsdl:output message="tns:getMapInfoResult"/>
    </wsdl:operation>
  </portType>

  <binding name="CartoserverBinding" type="tns:CartoserverPortType">
    <soap12:binding style="rpc" transport="http://schemas.xmlsoap.org/soap/http"/>
    <wsdl:operation name="getMap">
      <input>
        <soap12:body use="encoded" encodingStyle="http://www.w3.org/2003/05/soap-encoding" namespace="http://camptocamp.com/cartoserver"/>
      </input>
      <output>
        <soap12:body use="encoded" encodingStyle="http://www.w3.org/2003/05/soap-encoding" namespace="http://camptocamp.com/cartoserver"/>
      </output>
    </wsdl:operation>
    <wsdl:operation name="getMapInfo">
      <input>
        <soap12:body use="encoded" encodingStyle="http://www.w3.org/2003/05/soap-encoding" namespace="http://camptocamp.com/cartoserver"/>
      </input>
      <output>
        <soap12:body use="encoded" encodingStyle="http://www.w3.org/2003/05/soap-encoding" namespace="http://camptocamp.com/cartoserver"/>
      </output>
    </wsdl:operation>
  </binding>
  <service name="CartoserverService">
    <port name="CartoserverRpcPort" binding="tns:CartoserverBinding">
       <soap12:address location="<?php echo ((isset($_SERVER['HTTPS'])?"https://":"http://").$_SERVER['HTTP_HOST'].
           dirname($_SERVER['PHP_SELF']));?>/server.php"/>

    </port>
  </service>
</wsdl:definitions>
